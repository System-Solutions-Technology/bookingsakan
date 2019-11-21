<?php
namespace Modules\Booking\Gateways;

use Illuminate\Http\Request;
use Mockery\Exception;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Payment;
use Omnipay\Omnipay;
// use Omnipay\Stripe\Gateway;
// use Damas\Paytab as Paytabs;
use Omnipay\PayTabs\Gateway;
use PHPUnit\Framework\Error\Warning;
use Validator;
use Omnipay\Common\Exception\InvalidCreditCardException;
use Illuminate\Support\Facades\Log;

use App\Helpers\Assets;

class PaytabsGateway extends BaseGateway
{
    protected $id = 'Paytabs';

    public $name = 'Paytabs Checkout';

    protected $gateway;

    public function getOptionsConfigs()
    {
        return [
            [
                'type'  => 'checkbox',
                'id'    => 'enable',
                'label' => __('Enable Paytabs Standard?')
            ],
            [
                'type'  => 'input',
                'id'    => 'name',
                'label' => __('Custom Name'),
                'std'   => __("Paytabs")
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
                'id'        => 'paytabs_secret_key',
                'label'     => __('Secret Key'),
            ],
            [
                'type'       => 'input',
                'id'        => 'paytabs_publishable_key',
                'label'     => __('Publishable Key'),
            ]
            // ,
            // [
            //     'type'       => 'checkbox',
            //     'id'        => 'paytabs_enable_sandbox',
            //     'label'     => __('Enable Sandbox Mode'),
            // ],
            // [
            //     'type'       => 'input',
            //     'id'        => 'Paytabs',
            //     'label'     => __('Test Secret Key'),
            // ],
            // [
            //     'type'       => 'input',
            //     'id'        => 'paytabs_test_publishable_key',
            //     'label'     => __('Test Publishable Key'),
            // ]
        ];
    }

    public function process(Request $request, $booking, $service)
    {
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
        $pt = Paytabs::getInstance("info@bookingsakan.com", $this->getOption('paytabs_secret_key'));
        $payment = new Payment();
        $payment->booking_id = $booking->id;
        $payment->payment_gateway = $this->id;
        $payment->status = 'draft';
        
        $data = $this->handlePurchaseData([
            'amount'        => (float)$booking->total,
            'transactionId' => $booking->code . '.' . time()
        ], $booking, $payment);
        error_log("hey hey");
        error_log($booking->total);
	$result = $pt->create_pay_page(array(
		"merchant_email" => "info@bookingsakan.com",
		'secret_key' => $this->getOption('paytabs_secret_key'),
		'title' => "Booking Payment",
		'cc_first_name' => $request->first_name,
		'cc_last_name' => $request->last_name,
		'email' => $request->email,
		'cc_phone_number' => "0",
		'phone_number' => $request->phone,
		'billing_address' => $request->address_line_1.' '. $request->address_line_2,
		'city' => $request->city,
		'state' => $request->state,
		'postal_code' => $request->zip_code,
		'country' => 'USA',
		'address_shipping' => $request->address_line_1.' '. $request->address_line_2,
		'city_shipping' => $request->city,
		'state_shipping' => $request->state,
		'postal_code_shipping' => $request->zip_code,
		'country_shipping' => 'USA',
		"products_per_title"=> $booking->service->title,
		'currency' => setting_item('currency_main'),
		"unit_price"=> $booking->total,
		'quantity' => "1",
		'other_charges' => "0",
		'amount' => $booking->total,
		'discount'=>"0",
		"msg_lang" => "english",
		"reference_no" => $data['transactionId'],
		"site_url" => "https://bookingsakan.com",
        'return_url' => "http://localhost:8000/en/booking/".$data['transactionId']."/checkout",
        'cms_with_version'=>'Laravel 6'
	));
    // echo $result->response_code;
    error_log($result->response_code);
    if($result->response_code == 4012){
            $payment->save();
            $booking->status = $booking::UNPAID;
            $booking->payment_id = $payment->id;
            $booking->save();
            // redirect to offsite payment gateway
            response()->json([
                'url' => $result->payment_url
            ])->send();
	        // return redirect($result->payment_url);
        }
        else{
            throw new Exception('Paytabs Gateway: ' . $result->result);
        }
        
    }

    public function confirmPayment(Request $request)
    {
        $c = $request->query('c');
        $booking = Booking::where('code', $c)->first();
        if (!empty($booking) and in_array($booking->status, [$booking::UNPAID])) {
            
            $pt = Paytabs::getInstance("info@bookingsakan.com", $this->getOptionsConfigs('paytabs_secret_key'));
            $result = $pt->verify_payment($request->payment_reference);
            if ($result->response_code == 100) {
                $payment = $booking->payment;
                if ($payment) {
                    $payment->status = 'completed';
                    $payment->logs = \GuzzleHttp\json_encode($result->getData());
                    $payment->save();
                }
                try{
                    $booking->markAsPaid();
                } catch(\Swift_TransportException $e){
                    Log::warning($e->getMessage());
                }
                return redirect($booking->getDetailUrl())->with("success", __("You payment has been processed successfully"));
            } else {

                $payment = $booking->payment;
                if ($payment) {
                    $payment->status = 'fail';
                    $payment->logs = \GuzzleHttp\json_encode($result->getData());
                    $payment->save();
                }
                try{
                    $booking->markAsPaymentFailed();
                } catch(\Swift_TransportException $e){
                    Log::warning($e->getMessage());
                }
                return redirect($booking->getDetailUrl())->with("error", __("Payment Failed"));
            }
        }
        if (!empty($booking)) {
            return redirect($booking->getDetailUrl(false));
        } else {
            return redirect(url('/'));
        }
    }

    public function getGateway()
    {
        $this->gateway = Omnipay::create('Paytabs');
        $this->gateway->setApiKey($this->getOption('paytabs_secret_key'));
        // if ($this->getOption('paytabs_enable_sandbox')) {
        //     $this->gateway->setApiKey($this->getOption('paytabs_test_secret_key'));
        // }
    }

    public function handlePurchaseData($data, $booking, $request)
    {
        $data['currency'] = setting_item('currency_main');
        // $data['token'] = $request->input("token");
        $data['description'] = __("BookingSakan");
        return $data;
    }

    // public function getDisplayHtml()
    // {
    //     $script_inline = "
    //     <script src='https://paytabs.com/express/v4/paytabs-express-checkout.js'
    //     id='paytabs-express-checkout'
    //     data-secret-key='4UYAm5S9hsv7oNM7tybTalZkC7o6hfwl9uVwAOGShbZFYLMrkYlvjkPVsd2Jty4C209DPPv60vMYgyqBxM6Q7VMz9OiDnngpnp3c'
    //     data-merchant-id='info@bookingsakan.com'
    //     data-url-redirect='localhost'
    //     data-amount='10.0'
    //     data-currency='SAR'
    //     data-title='John Doe'
    //     data-product-names='Iphone'
    //     data-order-id='25'
    //     data-customer-phone-number='5486253'
    //     data-customer-email-address='john.deo@paytabs.com'
    //     data-customer-country-code='973'
    //  >
    //  </script>";
    //     // Assets::registerJs("https://js.paytabs.com/v3/",true);
    //     Assets::registerJs($script_inline,true,10,false,true);
    //     // Assets::registerJs( asset('module/booking/gateways/paytabs.js') ,true);
    //     $data = [
    //         'html' => $this->getOption('html', ''),
    //     ];
    //     return view("Booking::frontend.gateways.paytabs",$data);
    // }
}



define("TESTING", "https://localhost:8888/paytabs/apiv2/index");
define("AUTHENTICATION", "https://www.paytabs.com/apiv2/validate_secret_key");
define("PAYPAGE_URL", "https://www.paytabs.com/apiv2/create_pay_page");
define("VERIFY_URL", "https://www.paytabs.com/apiv2/verify_payment");

class Paytabs {

	private $merchant_id;
	private $merchant_secretKey;

	public static function getInstance($merchant_email, $merchant_secretKey)
	{
		static $inst = null;
		if ($inst === null) {
			$inst = new paytabs();
		}
		$inst->setMerchant($merchant_email, $merchant_secretKey);
		return $inst;
	}

	function setMerchant($merchant_email, $merchant_secretKey) {
		$this->merchant_email = $merchant_email;
		$this->merchant_secretKey = $merchant_secretKey;
		$this->api_key = "";
	}

	function authentication(){
		$obj = json_decode($this->runPost(AUTHENTICATION, array("merchant_email"=> $this->merchant_email, "merchant_secretKey"=>  $this->merchant_secretKey)));
		if($obj->access == "granted")
			$this->api_key = $obj->api_key;
		else
			$this->api_key = "";
		return $this->api_key;
	}

	function create_pay_page($values) {
		$values['merchant_email'] = $this->merchant_email;
		$values['merchant_secretKey'] = $this->merchant_secretKey;
		$values['ip_customer'] = $_SERVER['REMOTE_ADDR'];
		$values['ip_merchant'] = isset($_SERVER['SERVER_ADDR'])? $_SERVER['SERVER_ADDR'] : '::1';
		return json_decode($this->runPost(PAYPAGE_URL, $values));
	}

	function send_request(){
		$values['ip_customer'] = $_SERVER['REMOTE_ADDR'];
		$values['ip_merchant'] = isset($_SERVER['SERVER_ADDR'])? $_SERVER['SERVER_ADDR'] : '::1';
		return json_decode($this->runPost(TESTING, $values));
	}


	function verify_payment($payment_reference){
		$values['merchant_email'] = $this->merchant_email;
		$values['secret_key'] = $this->merchant_secretKey;
		$values['payment_reference'] = $payment_reference;
		return json_decode($this->runPost(VERIFY_URL, $values));
	}

	function runPost($url, $fields) {
		$fields_string = "";
		foreach ($fields as $key => $value) {
			$fields_string .= $key . '=' . $value . '&';
		}
		rtrim($fields_string, '&');
		$ch = curl_init();
		$ip = $_SERVER['REMOTE_ADDR'];

		$ip_address = array(
			"REMOTE_ADDR" => $ip,
			"HTTP_X_FORWARDED_FOR" => $ip
		);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $ip_address);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, 1);

		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

}
