<script src="{{ asset(mix('app.js', 'vendor/messenger')) }}"></script>
@stack('js')
@if(auth()->check())
<script src="https://cdn.jsdelivr.net/npm/emojione@4.0.0/lib/js/emojione.min.js"></script>
@endif
<script>
@if(auth()->check())
    Messenger.init({
        load : {
            NotifyManager : {
                notify_sound : {{messenger()->getProviderMessenger()->notify_sound}},
                message_popups : {{messenger()->getProviderMessenger()->message_popups}},
                message_sound : {{messenger()->getProviderMessenger()->message_sound}},
                call_ringtone_sound : {{messenger()->getProviderMessenger()->call_ringtone_sound}},
                src : 'NotifyManager.js'
            },
@stack('Messenger-load')

        },
        common : {
            model : '{{messenger()->getProviderAlias()}}',
            id : '{{messenger()->getProviderId()}}',
            name : '{{ messenger()->getProvider()->name()}}',
            slug : '{{ messenger()->getProvider()->getAvatarRoute('sm')}}',
            avatar_md : '{{ messenger()->getProvider()->getAvatarRoute('md')}}',
            mobile : false,
            base_css : '{{ asset(mix('app.css', 'vendor/messenger')) }}',
            dark_css : '{{ asset(mix('dark.css', 'vendor/messenger')) }}',
            {{-- websockets : false--}}
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
        modules : {
        @stack('Messenger-modules')
        },
        @stack('Messenger-call')
    }, '{{config('app.env')}}');
@endif
</script>
@stack('special-js')