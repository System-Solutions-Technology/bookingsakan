<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{$html_class ?? ''}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $favicon = setting_item('site_favicon');
    @endphp
    @if($favicon)
        @php
            $file = (new \Modules\Media\Models\MediaFile())->findById($favicon);
        @endphp
        @if(!empty($file))
            <link rel="icon" type="{{$file['file_type']}}" href="{{asset('uploads/'.$file['file_path'])}}" />
        @else:
            <link rel="icon" type="image/png" href="{{url('images/favicon.png')}}" />
        @endif
    @endif

    @include('Layout::parts.seo-meta')
    <link href="{{ asset('libs/bootstrap/css/bootstrap.css') }}" rel="stylesheet">
    <link href="{{ asset('libs/font-awesome/css/font-awesome.css') }}" rel="stylesheet">
    <link href="{{ asset('libs/ionicons/css/ionicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('libs/icofont/icofont.min.css') }}" rel="stylesheet">
    <link href="{{ asset('libs/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="{{ asset("libs/daterange/daterangepicker.css") }}" >
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel='stylesheet' id='google-font-css-css'  href='https://fonts.googleapis.com/css?family=Poppins%3A300%2C400%2C500%2C600' type='text/css' media='all' />
    {!! \App\Helpers\Assets::css() !!}
    {!! \App\Helpers\Assets::js() !!}
    <script>
        var bookingCore = {
            url:'{{url( app_get_locale() )}}',
            url_root:'{{ url('') }}',
            booking_decimals:{{(int)setting_item('currency_no_decimal',2)}},
            thousand_separator:'{{setting_item('currency_thousand')}}',
            decimal_separator:'{{setting_item('currency_decimal')}}',
            currency_position:'{{setting_item('currency_format')}}',
            currency_symbol:'{{currency_symbol()}}',
            date_format:'{{get_moment_date_format()}}',
            map_provider:'{{setting_item('map_provider')}}',
            map_gmap_key:'{{setting_item('map_gmap_key')}}',
            routes:{
                login:'{{route('auth.login')}}',
                register:'{{route('auth.register')}}',
            },
            currentUser:{{(int)Auth::id()}}
        };
        var i18n = {
            warning:"{{__("Warning")}}",
            success:"{{__("Success")}}",
        };
    </script>
    <script type="text/javascript" src="https://cdn.weglot.com/weglot.min.js"></script>
    <script>
        Weglot.on("switchersReady", function(initialLanguage) {
            console.log("the switchers are ready, I can tweak them")
            console.log($( '.dropdown-menu-lang' ).html());
            
            $( '.dropdown-menu-lang' ).html('<li><a href="http://'+Weglot.options.host+'/en" class="is_login"><span class="flag-icon flag-icon-gb"></span> English</a></li><li><a href="http://'+Weglot.options.host+'/ar"  class="is_login"><span class="flag-icon flag-icon-sa"></span> العربية</a></li><li><a href="https://'+Weglot.options.languages[0].connect_host_destination.host+'" class="is_login"><span class="flag-icon flag-icon-fr"></span> Français</a></li><li><a href="https://'+Weglot.options.languages[1].connect_host_destination.host+'" class="is_login"><span class="flag-icon flag-icon-ru"></span>  Pусский</a></li><li><a href="https://'+Weglot.options.languages[2].connect_host_destination.host+'" class="is_login"><span class="flag-icon flag-icon-tr"></span>  Türk</a></li>');
            console.log("after");
            console.log($( '.dropdown-menu-lang' ).html());
        })
        Weglot.initialize({
            api_key: 'wg_357507c3b95c0186b8ee686847ffdbd10'
        });
        
    </script>
    <!-- Styles -->
    @yield('head')
    {{--Custom Style--}}
    @include('Layout::parts.custom-css')
    <link href="{{ asset('libs/carousel-2/owl.carousel.css') }}" rel="stylesheet">
</head>
<body class="frontend-page {{$body_class ?? ''}}">
    {!! setting_item('body_scripts') !!}
    {!! setting_item_with_lang('body_scripts') !!}
    <div class="bravo_wrap">
{{--        @include('layouts.parts.adminbar')--}}
        @include('Layout::parts.topbar')
        @include('Layout::parts.header')
        @yield('content')
        @include('Layout::parts.footer')

    </div>
    {!! setting_item('footer_scripts') !!}
    {!! setting_item_with_lang('footer_scripts') !!}
    {{-- <script>$( '.dropdown-menu-lang' ).html('<li><a href="http://bookingsaken.com/en" class="is_login"><span class="flag-icon flag-icon-gb"></span> English</a></li><li><a href="http://bookingsaken.com/ar"  class="is_login"><span class="flag-icon flag-icon-sa"></span> العربية</a></li><li><a href="https://fr.bookingsaken.com" class="is_login"><span class="flag-icon flag-icon-fr"></span> Français</a></li><li><a href="https://ru.bookingsaken.com" class="is_login"><span class="flag-icon flag-icon-ru"></span>  Pусский</a></li><li><a href="https://tr.bookingsaken.com" class="is_login"><span class="flag-icon flag-icon-tr"></span>  Türk</a></li>');</script> --}}

</body>
</html>
