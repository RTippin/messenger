@extends('messenger::messenger')

@switch($mode)
    @case(0)
        @push('Messenger-load')
            ThreadManager : {
                type : 0,
                setup : true,
                online_status_setting : {{messenger()->getProviderMessenger()->online_status}},
                thread_id : '{{$thread_id}}',
                src : 'ThreadManager.js'
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
                src : 'ThreadManager.js'
            },
        @endpush
    @break
    @case(5)
        @push('Messenger-load')
            ThreadManager : {
                type : 5,
                online_status_setting : {{messenger()->getProviderMessenger()->online_status}},
                setup : true,
                src : 'ThreadManager.js'
            },
        @endpush
    @break
@endswitch
        @push('Messenger-modules')
            ThreadTemplates : {src : 'ThreadTemplates.js'},
            MessengerSettings : {src : 'MessengerSettings.js'},
        @endpush
