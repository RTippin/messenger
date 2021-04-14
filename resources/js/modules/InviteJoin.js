window.InviteJoin = (function () {
    var opt = {
        API : null,
        lock : true,
        invite : null,
        elements : {
            loading : $("#inv_loading"),
            loaded : $("#inv_loaded"),
            actions : $("#inv_actions_ctnr"),
            auth : $("#auth_flow")

        }
    },
    mounted = {
        Initialize : function (arg) {
            opt.lock = false;
            opt.API = Messenger.common().API + 'join/' + arg.code;
            methods.load()
        }
    },
    methods = {
        load : function(){
            Messenger.xhr().request({
                route : opt.API,
                success : function (invite) {
                    if(invite.options.is_valid){
                        opt.invite = invite;
                        opt.elements.loading.hide();
                        opt.elements.loaded.html(templates.header());
                        if(!invite.options.messenger_auth){
                            opt.elements.auth.show();
                        }
                        else{
                            opt.elements.actions.show();
                            if(invite.options.in_thread){
                                opt.elements.actions.html(templates.enter())
                            }
                            else{
                                opt.elements.actions.html(templates.join())
                            }
                        }
                        methods.setTitle(invite.options.messenger_auth);
                        PageListeners.listen().tooltips()
                    }
                    else{
                        methods.bad()
                    }
                },
                fail : methods.bad,
                bypass : true,
                fail_alert : true
            })
        },
        setTitle : function(auth){
            let title = 'Join ' + opt.invite.options.thread_name;
            document.title = title;
            if(auth){
                if(!Messenger.common().modules.includes('NotifyManager')){
                    setTimeout(function(){
                        methods.setTitle(auth)
                    }, 500);
                    return;
                }
                NotifyManager.setTitle(title)
            }
        },
        join : function(){
            if(opt.lock || !opt.invite || !opt.invite.options.is_valid || opt.invite.options.in_thread){
                return;
            }
            opt.lock = true;
            opt.elements.actions.html(Messenger.alert().loader(true));
            Messenger.xhr().payload({
                route : opt.API,
                data : {},
                success : function () {
                    window.location.replace(Messenger.common().WEB + '/' + opt.invite.thread_id)
                },
                fail : methods.bad,
                bypass : true,
                fail_alert : true
            })
        },
        bad : function(){
            opt.elements.loading.hide();
            opt.elements.loaded.html(templates.failed());
            opt.elements.actions.show();
            opt.elements.actions.html(templates.exit())
        }
    },
    templates = {
        join : function(){
            return '<button onclick="window.location.href = \''+Messenger.common().WEB+'\';" type="button" data-toggle="tooltip" data-placement="left" title="Cancel" class="mx-3 mb-4 shadow-lg btn btn-circle btn-circle-xl btn-danger">No <i class="fas fa-times"></i></button>\n' +
                '<button onclick="InviteJoin.join()" type="button" data-toggle="tooltip" data-placement="right" title="Join Group!" class="mx-3 mb-4 shadow-lg btn btn-circle btn-circle-xl btn-success">Join <i class="fas fa-users"></i></button>';
        },
        enter : function(){
            return '<button onclick="window.location.href=\''+Messenger.common().WEB+'/'+opt.invite.thread_id+'\'" type="button" data-toggle="tooltip"\n' +
                ' data-placement="bottom" title="View Group" class="shadow-lg btn btn-circle btn-circle-xl btn-success">Enter <i class="fas fa-users"></i></button>';
        },
        exit : function(){
            return '<button onclick="window.location.href = \'/\';" type="button" data-toggle="tooltip" data-placement="left" title="Cancel" class="mx-3 mb-4 shadow-lg btn btn-circle btn-circle-xl btn-danger">Exit <i class="fas fa-times"></i></button>';
        },
        failed : function(){
            return '<div class="float-right d-none d-sm-block pl-2">\n' +
                ' <img class="pl-2" id="FSlog" height="95" src="'+[window.location.protocol, '//', window.location.host].join('')+'/vendor/messenger/images/messenger.png">\n' +
                ' </div>' +
                '<h1 class="display-4"><i class="fas fa-exclamation-triangle"></i> Invalid Invite</h1>\n' +
                ' <p class="h3 mt-4">\n' +
                '  <i class="far fa-dot-circle"></i>\n' +
                ' The invite you requested is expired or not found.'+
                ' </p>';
        },
        header : function(){
            return '<div class="float-right d-none d-sm-block pl-2">\n' +
                ' <img onclick="Messenger.alert().showAvatar(\''+opt.invite.options.thread_name+'\', \''+opt.invite.options.avatar.lg+'\')" class="pl-2 rounded-circle pointer_area" id="FSlog" height="105" src="'+opt.invite.options.avatar.sm+'">\n' +
                ' </div>' +
                '<h1 class="display-4"><i class="fas fa-users"></i> '+opt.invite.options.thread_name+'</h1>\n' +
                '<div class="text-center d-block d-sm-none mt-4">\n' +
                ' <img onclick="Messenger.alert().showAvatar(\''+opt.invite.options.thread_name+'\', \''+opt.invite.options.avatar.lg+'\')" class="pl-2 rounded-circle pointer_area" id="FSlog" height="75" src="'+opt.invite.options.avatar.sm+'">\n' +
                ' </div>' +
                ' <p class="h3 mt-4">\n' +
                '  <i class="far fa-dot-circle"></i>\n' +
                    templates.message()+
                ' </p>';
        },
        message : function(){
            if(!opt.invite.options.messenger_auth){
                return 'Before you may join the group, you must log in or sign up below.'
            }
            if(opt.invite.options.in_thread){
                return 'You are already in this group.';
            }
            return 'Select an option below. Once joined, you will be redirected into the group.';
        }
    };
    return {
        init : mounted.Initialize,
        join : methods.join,
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());