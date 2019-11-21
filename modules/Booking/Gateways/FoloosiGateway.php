<?php
namespace Modules\Booking\Gateways;

use Illuminate\Http\Request;
use Mockery\Exception;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Payment;
use PHPUnit\Framework\Error\Warning;
use Validator;
use Illuminate\Support\Facades\Log;

use App\Helpers\Assets;

class FoloosiGateway extends BaseGateway
{
    protected $id = 'Foloosi';

    public $name = 'Foloosi Checkout';

    protected $gateway;

    public function getOptionsConfigs()
    {
        return [
            [
                'type'  => 'checkbox',
                'id'    => 'enable',
                'label' => __('Enable Foloosi Standard?')
            ],
            [
                'type'  => 'input',
                'id'    => 'name',
                'label' => __('Custom Name'),
                'std'   => __("Foloosi")
            ],
            [
                'type'  => 'upload',
                'id'    => 'logo_id',
                'label' => __('Custom Logo'),
            ],
            [
                'type'  => 'editor',
                'id'    => 'html',
                'label' => __('Custom HTML Description')
            ],
            [
                'type'       => 'input',
                'id'        => 'foloosi_secret_key',
                'label'     => __('Secret Key'),
            ],
            [
                'type'       => 'input',
                'id'        => 'foloosi_merchant_key',
                'label'     => __('Merchant Key'),
            ]
        ];
    }

    public function process(Request $request, $booking, $service){
        if (in_array($booking->status, [
            $booking::PAID,
            $booking::COMPLETED,
            $booking::CANCELLED
        ])) {

            throw new Exception(__("Booking status does need to be paid"));
        }
        if (!$booking->total) {
            throw new Exception(__("Booking total is zero. Can not process payment gateway!"));
        }
        
        $payment = new Payment();
        $payment->booking_id = $booking->id;
        $payment->payment_gateway = $this->id;
        $payment->status = 'draft';
        
        $data = $this->handlePurchaseData([
            'amount'        => (float)$booking->total,
            'transactionId' => $booking->code . '.' . time()
        ], $booking, $payment);

        $url = 'https://foloosi.com/api/v1/api/initialize-setup';
        
        //Create a cURL handle.
        $ch = curl_init($url);
        $post = [
            'redirect_url' => '',
            'transaction_amount' => $booking->total,
            'currency'   => setting_item('currency_main'),
            'customer_name'=>$request->first_name.' '.$request->last_name,
            'customer_email'=>$request->email,
            'customer_mobile'=>$request->phone,
            'customer_address'=>$request->address_line_1.' '. $request->address_line_2,
            'customer_city'=>$request->city
        ];
        
        //Create an array of custom headers.
        $customHeaders = array(
            'merchant_key:'.$this->getOption('foloosi_merchant_key')
            
        );
        
        //Use the CURLOPT_HTTPHEADER option to use our
        //custom headers.
        curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        //Set options to follow redirects and return output
        //as a string.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        //Execute the request.
        $result = curl_exec($ch);
        
        $result = json_decode($result);
        printf( $result->data->reference_token);
        echo '<script type="text/javascript">
        var options = {
            "reference_token" : "'.$result->data->reference_token.'",
            "merchant_key" : "live_$2y$10$VK6LJ9xEz6LxmI826NmrFOCGtCYhZyxC10tNmvLct4su8felYZCy2"
        }
        var fp1 = new Foloosipay(options);
        function pay(){
            var fp1 = new Foloosipay(options);
            fp1.open();
        }
    </script>
    <script type="text/javascript" src="https://www.foloosi.com/js/foloosipay.v2.js"></script>
    <body><a href="#" onclick="pay();">pop</a></body>
    ';
    }

}