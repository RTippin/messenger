<script src="{{ asset(mix('app.js', 'vendor/messenger')) }}"></script>
@stack('js')
@if(auth()->check())
<script src="https://cdn.jsdelivr.net/npm/emoji-toolkit@6.5.1/lib/js/joypixels.min.js"></script>
@endif
<script>
@if(auth()->check())
    Messenger.init({
        load : {
            NotifyManager : {
                notify_sound : {{messenger()->getProviderMessenger()->notify_sound ? 'true' : 'false'}},
                message_popups : {{messenger()->getProviderMessenger()->message_popups ? 'true' : 'false'}},
                message_sound : {{messenger()->getProviderMessenger()->message_sound ? 'true' : 'false'}},
                call_ringtone_sound : {{messenger()->getProviderMessenger()->call_ringtone_sound ? 'true' : 'false'}},
                src : 'NotifyManager.js'
            },
@stack('Messenger-load')

        },
        provider : {
            model : '{{messenger()->getProviderAlias()}}',
            @if(config('messenger.provider_uuids'))
                id : '{{messenger()->getProvider()->getKey()}}',
            @else
                id : {{messenger()->getProvider()->getKey()}},
            @endif
            name : '{{ messenger()->getProvider()->getProviderName()}}',
            slug : '{{ messenger()->getProvider()->getProviderAvatarRoute('sm')}}',
            avatar_md : '{{ messenger()->getProvider()->getProviderAvatarRoute('md')}}',
        },
        common : {
            app_name : '{{messenger()->getSiteName()}}',
            api_endpoint : '{{messenger()->getApiEndpoint()}}',
            web_endpoint : '{{messenger()->getWebEndpoint()}}',
            socket_endpoint : '{{messenger()->getSocketEndpoint()}}',
            base_css : '{{ asset(mix('app.css', 'vendor/messenger')) }}',
            dark_css : '{{ asset(mix('dark.css', 'vendor/messenger')) }}',
            dark_mode : {{messenger()->getProviderMessenger()->dark_mode ? 'true' :  'false'}},
            mobile : false,
        },
        modules : {
@stack('Messenger-modules')

        },
@stack('Messenger-call')
}, '{{config('app.env')}}');
@else
    Messenger.init({
        load : {
        @stack('Messenger-load')
        },
        common : {
            app_name : '{{messenger()->getSiteName()}}',
            api_endpoint : '{{messenger()->getApiEndpoint()}}',
            web_endpoint : '{{messenger()->getWebEndpoint()}}',
            socket_endpoint : '{{messenger()->getSocketEndpoint()}}',
            base_css : '{{ asset(mix('app.css', 'vendor/messenger')) }}',
            dark_css : '{{ asset(mix('dark.css', 'vendor/messenger')) }}',
            dark_mode : true,
            mobile : false,
        },
        modules : {
        @stack('Messenger-modules')
        },
        @stack('Messenger-call')
    }, '{{config('app.env')}}');
@endif
</script>
@stack('special-js')