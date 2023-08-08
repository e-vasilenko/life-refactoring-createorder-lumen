<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Order;
use App\Models\PayAgent;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JSendResponse\JSendResponse;
use YooKassa\Client;

class CreateOrderController3 extends Controller
{
    // В примере используются модели Eloquent\Model
    public function __invoke($request)
    {
        $responseGen = new JSendResponse();

        if ($request->get('mine_host') == "") {
            return $responseGen->responseError('mine_host - обязательное поле', 402);
        }

        $project = Project::firstWhere('alias', '=', $request->get('mine_host'));
        $payAgent = PayAgent::firstWhere('name', '=', $request->get("payment_method"));
        if ($payAgent->id == null) {
            $payAgentВId = 1;
        } else {
            $payAgentВId = $payAgent->id;
        }

        $contract = Contract::where('farm_id', '=', $payAgent->id)
            ->where('project_id', '=', $project->id)
            ->get()
            ->first();
        if ($contract === null) {
            throw new \Exception("Контракт не найден");
        }

        Http::post("http://billing.local/check_account", [
            'account' => $request->get("account"),
            'amount' => $request->get("amount"),
        ]);

        $amount = $request->get('amount');
        $commissionAmount = $amount * 3 / 100;

        if ($commissionAmount < 15) {
            $commissionAmount = 15;
        }
        $amount = $amount + $commissionAmount;

        // YooKassa client
        $client = new Client();
        $client->setAuth('123456', 'test_123456');
        $idempotenceKey = Str::uuid()->toString();

        $payAgentOrder = $client->createPayment(
            [
                'amount' => [
                    'value' => $amount,
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => 'http://billing.local/bill',
                ],
                'description' => $request->get("description"),
            ],
            $idempotenceKey
        );

        $savedOrder = new Order();
        $savedOrder->fill([
            'account' => $request->get('account'),
            'payment_amount' => $request->get('amount'),
            'billing_amount' => $amount,
            'commission_amount' => $commissionAmount,
            'description' => $request->get('description'),
            'payment_method' => $request->get('payment_method'),
            'payment' => $payAgentOrder->getId(),
        ]);

        $savedOrder->save();

        return $responseGen->responseSuccess([
            'amount' => $savedOrder->billing_amount,
            'account' => $savedOrder->account,
        ]);
    }
}
