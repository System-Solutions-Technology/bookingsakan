@php
    $languages = \Modules\Language\Models\Language::getActive();
    $locale = session('website_locale',app()->getLocale());
@endphp
{{--Multi Language--}}
@if(!empty($languages) && setting_item('site_enable_multi_lang'))
    <li class="dropdown">
        @foreach($languages as $language)
            @if($locale == $language->locale)
                <a href="#" data-toggle="dropdown" class="is_login">
                    @if($language->flag)
                        <span class="flag-icon flag-icon-{{$language->flag}}"></span>
                    @endif
                    {{$language->name}}
                    <i class="fa fa-angle-down"></i>
                </a>
            @endif
        @endforeach
        <ul class="dropdown-menu text-left">
            {{-- @foreach($languages as $language)
                @if($locale != $language->locale)
                    <li>
                        <a href="{{get_lang_switcher_url($language->locale)}}" class="is_login">
                            @if($language->flag)
                                <span class="flag-icon flag-icon-{{$language->flag}}"></span>
                            @endif
                            {{$language->name}}
                        </a>
                    </li>
                @endif
            @endforeach --}}
            
            <li><a href="http://en.bookingsaken.com"class="is_login"><span class="flag-icon flag-icon-gb"></span> English</a></li>
            <li><a href="http://ar.bookingsaken.com"class="is_login"><span class="flag-icon flag-icon-sa"></span> العربية</a></li>
            <li><a href="https://fr.bookingsaken.com"class="is_login"><span class="flag-icon flag-icon-fr"></span> Français</a></li>
            <li><a href="https://ru.bookingsaken.com"class="is_login"><span class="flag-icon flag-icon-ru"></span> Pусский</a></li>
            <li><a href="https://tr.bookingsaken.com"class="is_login"><span class="flag-icon flag-icon-tr"></span> Türk</a></li>
        </ul>
    </li>
@endif
{{--End Multi language--}}