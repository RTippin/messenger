<script src="{{ mix('js/app.js') }}"></script>
@if(auth()->check())
<script src="{{mix("js/managers/NotifyManager.js")}}"></script>
@endif
@stack('js')
@if(auth()->check())
<script src="https://cdn.jsdelivr.net/npm/emojione@4.0.0/lib/js/emojione.min.js"></script>
@endif
<script>
@if(auth()->check())
    Messenger.init({
        load : {
            NotifyManager : {
                notify_sound : {{messenger()->getProvider()->messenger->notify_sound}},
                message_popups : {{messenger()->getProvider()->messenger->message_popups}},
                message_sound : {{messenger()->getProvider()->messenger->message_sound}},
                call_ringtone_sound : {{messenger()->getProvider()->messenger->call_ringtone_sound}},
                src : '{{mix("js/managers/NotifyManager.js")}}'
            },
@stack('Messenger-load')

        },
        common : {
            model : '{{messenger()->getProviderAlias()}}',
            id : '{{messenger()->getProviderId()}}',
            name : '{{ messenger()->getProvider()->name()}}',
            slug : '{{ messenger()->getProvider()->getAvatarRoute('sm')}}',
            avatar_md : '{{ messenger()->getProvider()->getAvatarRoute('md')}}',
            mobile : {{ agent()->isMobile() ? 'true' : 'false' }},
            base_css : '{{ mix("css/app.css") }}',
            dark_css : '{{ mix("css/dark.css") }}',
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