@extends('messenger')

@push('js')
{{--<script src="{{mix("js/managers/ThreadManager.js")}}"></script>--}}
{{--<script src="{{mix("js/templates/ThreadTemplates.js")}}"></script>--}}
{{--<script src="{{mix("js/modules/MessengerSettings.js")}}"></script>--}}
@endpush

@switch($mode)
    @case(0)
        @push('Messenger-load')
            ThreadManager : {
                type : 0,
                setup : true,
                online_status_setting : {{messenger()->getProviderMessenger()->online_status}},
                thread_id : '{{$thread_id}}',
{{--                src : '{{mix("js/managers/ThreadManager.js")}}'--}}
            },
        @endpush
    @break
    @case(3)
        @push('Messenger-load')
            ThreadManager : {
                type : 3,
                online_status_setting : {{messenger()->getProviderMessenger()->online_status}},
                setup : true,
                id : '{{$id}}',
                alias : '{{$alias}}',
{{--                src : '{{mix("js/managers/ThreadManager.js")}}'--}}
            },
        @endpush
    @break
    @case(5)
        @push('Messenger-load')
            ThreadManager : {
                type : 5,
                online_status_setting : {{messenger()->getProviderMessenger()->online_status}},
                setup : true,
{{--                src : '{{mix("js/managers/ThreadManager.js")}}'--}}
            },
        @endpush
    @break
@endswitch
{{--        @push('Messenger-modules')--}}
{{--            ThreadTemplates : {src : '{{mix("js/templates/ThreadTemplates.js")}}'},--}}
{{--            MessengerSettings : {src : '{{mix("js/modules/MessengerSettings.js")}}'},--}}
{{--        @endpush--}}
