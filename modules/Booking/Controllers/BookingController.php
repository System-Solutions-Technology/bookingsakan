<?php
namespace Modules\Booking\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Mockery\Exception;
//use Modules\Booking\Events\VendorLogPayment;
use Modules\Media\Helpers\FileHelper;
use Modules\Tour\Models\TourDate;

use Modules\Booking\Models\Payment;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Models\Booking;
use App\Helpers\ReCaptchaEngine;

class BookingController extends \App\Http\Controllers\Controller
{
    use AuthorizesRequests;
    protected $booking;

    public function __construct()
    {
        $this->booking = Booking::class;
    }

    public function cUrlGetData($url, $post_fields = null, $headers = null) {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($post_fields && !empty($post_fields)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        }
        if ($headers && !empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $data = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $data;
    }
    public function foloosiPay(Request $request){

        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            $this->sendError('', ['errors' => $validator->errors()]);
        }
        $code = $request->input('code');
        $booking = $this->booking::where('code', $code)->first();

        if (empty($booking)) {
            abort(404);
        }
        if ($booking->customer_id != Auth::id()) {
            abort(404);
        }
        if ($booking->status != 'draft') {
            return $this->sendError('',[
                'url'=>$booking->getDetailUrl()
            ]);
        }
        $service = $booking->service;
        if (empty($service)) {
            $this->sendError(__("Service not found"));
        }
        /**
         * Google ReCapcha
         */
        if(ReCaptchaEngine::isEnable() and setting_item("booking_enable_recaptcha")){
            $codeCapcha = $request->input('g-recaptcha-response');
            if(!$codeCapcha or !ReCaptchaEngine::verify($codeCapcha)){
                $this->sendError(__("Please verify the captcha"));
            }
        }
        $rules = [
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => 'required|string|email|max:255',
            'phone'           => 'required|string|max:255',
            // 'payment_gateway' => 'required',
            'term_conditions' => 'required'
        ];
        $request->payment_gateway = 'foloosi';
        $rules = $service->filterCheckoutValidate($request, $rules);
        if (!empty($rules)) {
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $this->sendError('', ['errors' => $validator->errors()]);
            }
        }
        if (!empty($rules['payment_gateway'])) {
            $payment_gateway = $request->input('payment_gateway');
            $gateways = config('booking.payment_gateways');
            if (empty($gateways[$payment_gateway]) or !class_exists($gateways[$payment_gateway])) {
                $this->sendError(__("Payment gateway not found"));
            }
            $gatewayObj = new $gateways[$payment_gateway]($payment_gateway);
            if (!$gatewayObj->isAvailable()) {
                $this->sendError(__("Payment gateway is not available"));
            }
        }
        $service->beforeCheckout($request, $booking);
        // Normal Checkout
        $booking->first_name = $request->input('first_name');
        $booking->last_name = $request->input('last_name');
        $booking->email = $request->input('email');
        $booking->phone = $request->input('phone');
        $booking->address = $request->input('address_line_1');
        $booking->address2 = $request->input('address_line_2');
        $booking->city = $request->input('city');
        $booking->state = $request->input('state');
        $booking->zip_code = $request->input('zip_code');
        $booking->country = $request->input('country');
        $booking->customer_notes = $request->input('customer_notes');
        $booking->gateway = 'foloosi';
        $booking->save();

        $booking->addMeta('locale',app()->getLocale());

        $service->afterCheckout($request, $booking);
        try {
        $url = 'https://foloosi.com/api/v1/api/initialize-setup';

        //Create a cURL handle.
        $ch = curl_init($url);
        $post = [
            'redirect_url' => 'www.google.com',
            'transaction_amount' => $booking->total,
            'currency'   => 'USD',
            'customer_name'=>$request->input('first_name').' '.$request->input('last_name'),
            'customer_email'=>$request->input('email'),
            'customer_mobile'=>$request->input('phone'),
            'customer_address'=>$request->input('address_line_1').' '.$request->input('address_line_2'),
            'customer_city'=>$request->input('city')
        ];

        //Create an array of custom headers.
        $customHeaders = array(
            'secret_key:live_$2y$10$YSDdl.VOHfuZ74mFpV.1juJ6MShTowbyl3.lHGYuxkXdog2oe.Rfa'
            // 'merchant_key:test_$2y$10$TuB4vGz4kPwGkjgDyrVRA.B0JsLJ.0a3G8ykTcTD8fTwTTZrT2BqW'

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
        // printf( $result->data->reference_token);
        $html= '<script type="text/javascript">
        var options = {
            "reference_token" : "'.$result->data->reference_token.'",
            "merchant_key" : "live_$2y$10$VK6LJ9xEz6LxmI826NmrFOCGtCYhZyxC10tNmvLct4su8felYZCy2"
        }
        var fp1 ;
        function pay(){
            fp1 = new Foloosipay(options);
            fp1.open();
        }
    </script>

    ';
            // $gatewayObj->process($request, $booking, $service);
            // error_log($html);


        // if (in_array($booking->status, [
        //     $booking::PAID,
        //     $booking::COMPLETED,
        //     $booking::CANCELLED
        // ])) {

        //     throw new Exception(__("Booking status does need to be paid"));
        // }
        // if (!$booking->total) {
        //     throw new Exception(__("Booking total is zero. Can not process payment gateway!"));
        // }
        $payment = new Payment();
        error_log('hey');
        $payment->booking_id = $booking->id;
        $payment->payment_gateway = 'foloosi';
        $payment->status = 'draft';
        // $data = $this->handlePurchaseData([
        //     'amount'        => (float)$booking->total,
        //     'transactionId' => $booking->code . '.' . time()
        // ], $booking, $payment);
        // error_log(json_encode(
        //     ['success' => true,
        //     'data'   => $html]
        // ));
            return $html;
            return json_encode(
                ['success' => true,
                'data'   => $html]
            );
        } catch (Exception $exception) {
            error_log('tesssst');
            $this->sendError($exception->getMessage());
        }
        //----------------------------

    }
    public function confirm(Request $request){
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            $this->sendError('', ['errors' => $validator->errors()]);
        }
        $code = $request->input('code');
        $booking = $this->booking::where('code', $code)->first();

        if (empty($booking)) {
            abort(404);
        }
        if ($booking->customer_id != Auth::id()) {
            abort(404);
        }
        if ($booking->status != 'draft') {
            return $this->sendError('',[
                'url'=>$booking->getDetailUrl()
            ]);
        }
        $payment = $booking->payment;
        if ($payment) {
            $payment->status = 'completed';
            $payment->logs = \GuzzleHttp\json_encode($response->getData());
            $payment->save();
        }
        try{
            $booking->markAsPaid();
        } catch(\Swift_TransportException $e){
            Log::warning($e->getMessage());
        }
        error_log($booking->getDetailUrl());
        response()->json([
            'url' => $booking->getDetailUrl(),
            "success", __("You payment has been processed successfully")
        ])->send();
        // return redirect($booking->getDetailUrl())->with("success", __("You payment has been processed successfully"));
    }
    public function checkout($code)
    {
        $booking = $this->booking::where('code', $code)->first();
        if (empty($booking)) {
            abort(404);
        }
        if ($booking->customer_id != Auth::id()) {
            abort(404);
        }

        if($booking->status != 'draft'){
            return redirect('/');
        }
        $data = [
            'page_title' => __('Checkout'),
            'booking'    => $booking,
            'service'    => $booking->service,
            'gateways'   => $this->getGateways(),
            'user'       => Auth::user()
        ];

        return view('Booking::frontend/checkout', $data);
    }

    public function checkStatusCheckout($code)
    {
        $booking = $this->booking::where('code', $code)->first();
        $data = [
            'error'    => false,
            'message'  => '',
            'redirect' => ''
        ];
        if (empty($booking)) {
            $data = [
                'error'    => true,
                'redirect' => url('/')
            ];
        }
        if ($booking->customer_id != Auth::id()) {
            $data = [
                'error'    => true,
                'redirect' => url('/')
            ];
        }
        if ($booking->status != 'draft') {
            $data = [
                'error'    => true,
                'redirect' => url('/')
            ];
        }
        return response()->json($data, 200);
    }

    public function doCheckout(Request $request)
    {
        /**
         * @param Booking $booking
         */


        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) {
            $this->sendError('', ['errors' => $validator->errors()]);
        }
        $code = $request->input('code');
        $booking = $this->booking::where('code', $code)->first();

        if (empty($booking)) {
            abort(404);
        }
        if ($booking->customer_id != Auth::id()) {
            abort(404);
        }
        if ($booking->status != 'draft') {
            return $this->sendError('',[
                'url'=>$booking->getDetailUrl()
            ]);
        }
        $service = $booking->service;
        if (empty($service)) {
            $this->sendError(__("Service not found"));
        }
        /**
         * Google ReCapcha
         */
        if(ReCaptchaEngine::isEnable() and setting_item("booking_enable_recaptcha")){
            $codeCapcha = $request->input('g-recaptcha-response');
            if(!$codeCapcha or !ReCaptchaEngine::verify($codeCapcha)){
                $this->sendError(__("Please verify the captcha"));
            }
        }
        $rules = [
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'required|string|max:255',
            'email'           => 'required|string|email|max:255',
            'phone'           => 'required|string|max:255',
            'payment_gateway' => 'required',
            'term_conditions' => 'required'
        ];
        $rules = $service->filterCheckoutValidate($request, $rules);
        if (!empty($rules)) {
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $this->sendError('', ['errors' => $validator->errors()]);
            }
        }
        if (!empty($rules['payment_gateway'])) {
            $payment_gateway = $request->input('payment_gateway');
            $gateways = config('booking.payment_gateways');
            if (empty($gateways[$payment_gateway]) or !class_exists($gateways[$payment_gateway])) {
                $this->sendError(__("Payment gateway not found"));
            }
            $gatewayObj = new $gateways[$payment_gateway]($payment_gateway);
            if (!$gatewayObj->isAvailable()) {
                $this->sendError(__("Payment gateway is not available"));
            }
        }
        $service->beforeCheckout($request, $booking);
        // Normal Checkout
        $booking->first_name = $request->input('first_name');
        $booking->last_name = $request->input('last_name');
        $booking->email = $request->input('email');
        $booking->phone = $request->input('phone');
        $booking->address = $request->input('address_line_1');
        $booking->address2 = $request->input('address_line_2');
        $booking->city = $request->input('city');
        $booking->state = $request->input('state');
        $booking->zip_code = $request->input('zip_code');
        $booking->country = $request->input('country');
        $booking->customer_notes = $request->input('customer_notes');
        $booking->gateway = $payment_gateway;
        $booking->save();

//        event(new VendorLogPayment($booking));

        $user = Auth::user();
        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->phone = $request->input('phone');
        $user->address = $request->input('address_line_1');
        $user->address2 = $request->input('address_line_2');
        $user->city = $request->input('city');
        $user->state = $request->input('state');
        $user->zip_code = $request->input('zip_code');
        $user->country = $request->input('country');
        $user->save();

        $booking->addMeta('locale',app()->getLocale());

        $service->afterCheckout($request, $booking);
        try {

            $gatewayObj->process($request, $booking, $service);
        } catch (Exception $exception) {
            $this->sendError($exception->getMessage());
        }
    }

    public function confirmPayment(Request $request, $gateway)
    {

        $gateways = config('booking.payment_gateways');
        if (empty($gateways[$gateway]) or !class_exists($gateways[$gateway])) {
            $this->sendError(__("Payment gateway not found"));
        }
        $gatewayObj = new $gateways[$gateway]($gateway);
        if (!$gatewayObj->isAvailable()) {
            $this->sendError(__("Payment gateway is not available"));
        }
        return $gatewayObj->confirmPayment($request);
    }

    public function cancelPayment(Request $request, $gateway)
    {

        $gateways = config('booking.payment_gateways');
        if (empty($gateways[$gateway]) or !class_exists($gateways[$gateway])) {
            $this->sendError(__("Payment gateway not found"));
        }
        $gatewayObj = new $gateways[$gateway]($gateway);
        if (!$gatewayObj->isAvailable()) {
            $this->sendError(__("Payment gateway is not available"));
        }
        return $gatewayObj->cancelPayment($request);
    }

    /**
     * @todo Handle Add To Cart Validate
     *
     * @param Request $request
     * @return string json
     */
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id'   => 'required|integer',
            'service_type' => 'required',
            //'timeshare_years' => 'optional'
        ]);


        if ($validator->fails()) {
            $this->sendError('', ['errors' => $validator->errors()]);
        }

        $service_type = $request->input('service_type');
        $service_id = $request->input('service_id');
        $allServices = get_bookable_services();
        if (empty($allServices[$service_type])) {
            $this->sendError(__('Service type not found'));
        }
        $module = $allServices[$service_type];
        $service = $module::find($service_id);
        if (empty($service) or !is_subclass_of($service, '\\Modules\\Booking\\Models\\Bookable')) {
            $this->sendError(__('Service not found'));
        }
        if (!$service->isBookable()) {
            $this->sendError(__('Service is not bookable'));
        }
        //        try{
        $service->addToCart($request);
        //
        //        }catch(\Exception $ex){
        //            $this->sendError($ex->getMessage(),['code'=>$ex->getCode()]);
        //        }
    }

    protected function getGateways()
    {

        $all = config('booking.payment_gateways');
        $res = [];
        foreach ($all as $k => $item) {
            if (class_exists($item)) {
                $obj = new $item($k);
                if ($obj->isAvailable()) {
                    $res[$k] = $obj;
                }
            }
        }
        return $res;
    }

    public function detail(Request $request, $code)
    {

        $booking = Booking::where('code', $code)->first();
        if (empty($booking)) {
            abort(404);
        }

        if ($booking->status == 'draft') {
            return redirect($booking->getCheckoutUrl());
        }
        if ($booking->customer_id != Auth::id()) {
            abort(404);
        }
        $data = [
            'page_title' => __('Booking Details'),
            'booking'    => $booking,
            'service'    => $booking->service,
        ];
        if ($booking->gateway) {
            $data['gateway'] = get_payment_gateway_obj($booking->gateway);
        }
        return $this->view('Booking::frontend/detail', $data, $request);
    }

    public function details(Request $request)
    {

        $bookings = Booking::where('customer_id', Auth::id())->get();
        if (empty($bookings)) {
            abort(404);
        }

        foreach ($bookings as $booking) {
            $booking['gateway'] = get_payment_gateway_obj($booking->gateway);
        }
         return response()->json($bookings);
    }
}
