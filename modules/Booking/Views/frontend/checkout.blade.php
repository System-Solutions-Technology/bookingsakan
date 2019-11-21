@extends('layouts.app')
@section('head')
    <link href="{{ asset('module/booking/css/checkout.css?_ver='.config('app.version')) }}" rel="stylesheet">
@endsection
@section('content')
    <div class="bravo-booking-page padding-content" >
        <div class="container">
            <div id="bravo-checkout-page" >
                <div class="row">
                    <div class="col-md-8">
                        <h3 class="form-title">{{__('Booking Submission')}}</h3>
                         <div class="booking-form">
                             @include ($service->checkout_form_file ?? 'Booking::frontend/booking/checkout-form')
                            
                         </div>
                    </div>
                    <div class="col-md-4">
                        <div class="booking-detail">
                            @include ($service->checkout_booking_detail_file ?? '')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('footer')
    <script src="{{ asset('module/booking/js/checkout.js') }}"></script>
    <script type="text/javascript" src="https://www.foloosi.com/js/foloosipay.v2.js"></script>
    <script type="text/javascript">
        jQuery(function () {
            $.ajax({
                'url': bookingCore.url + '/booking/{{$booking->code}}/check-status',
                'cache': false,
                'type': 'GET',
                success: function (data) {
                    if (data.redirect !== undefined && data.redirect) {
                        window.location.href = data.redirect
                    }
                }
            });
        })
        var ret;
        $('#booknow').click(function () {
            // var data = $(this).children('option:selected').data('id');
            $('.alert-text').css("display","none");
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.ajax({
                type: "POST",
                url: bookingCore.url + '/booking/foloosiPay',
                // dataType:"html",
                data: $('.booking-form').find('input,textarea,select').serialize(),

                success: function (response) {
                    ret = response;
                    $('#script-loader').html(response);
                    
                    pay();
                },
                error: function (response) {
                    $('.alert-text').html('Payement Gateway Error !');
                    $('.alert-text').css("display","block");
                    console.error(response);
                },
                
            })
        });
        foloosiHandler(response, function (e) {
        if(e.data.status == 'success'){
            //responde success code
            console.log(e.data.status);
            console.log(e.data.data);
            fp1.close();
            $.ajax({
                type: "POST",
                url: bookingCore.url + '/booking/confirm',
                // dataType:"html",
                data: $('.booking-form').find('input,textarea,select').serialize(),

                success: function (response) {
                    ret = response;
                    if(response.url){
                        window.location.href = response.url
                    }
                },
                error: function (response) {
                    alert('erreur');
                    console.error(response);
                },
                
            })
        }
        if(e.data.status == 'error'){
            fp1.close();
            $('.alert-text').html('Your Payement didn\'t go as expected, please try again!');
            $('.alert-text').css("display","block");
        }
        });
    </script>
@endsection