window.CallManager = (function () {
    var opt = {
        initialized : false,
        INIT_time : null,
        API : Messenger.common().API,
        demo : false,
        processing : false,
        _call : null,
        call : false,
        call_loaded : false,
        call_id : null,
        call_mode : null,
        created_at : null,
        call_type : null,
        call_owner : null,
        thread_id : null,
        thread_type : null,
        thread_name : null,
        call_admin : null,
        thread_admin : null,
        room_id : null,
        room_pin : null,
        janus_secret : null,
        janus_debug : false,
        janus_ice : [],
        janus_main : [],
        channel : null,
        channel_status : false,
        active_profiles : [],
        heartbeat_interval : null,
        heartbeat_retries : 0,
        elements : {
            call_status : $("#call_status"),
            call_status_msg : $("#call_status_msg"),
            call_status_body : $("#call_status_body"),
            main_call : $("#main_call"),
        }
    },
    mounted = {
        Initialize : function(arg){
            if(opt.initialized) return;
            opt.call = true;
            opt.janus_secret = arg.janus_secret;
            opt.janus_debug = arg.janus_debug;
            opt.janus_main = arg.janus_main;
            opt.janus_ice = arg.janus_ice;
            opt.initialized = true;
            if(arg.hasOwnProperty('demo'))
            {
                opt.call_loaded = true;
                opt.demo = true;
                opt.room_id = parseInt(arg.room_id);
                opt.room_pin = arg.room_pin;
                Sockets.setupRTC();
                return;
            }
            mounted.loadCall(arg.thread_id, arg.call_id, mounted.setup);
        },
        setup : function(call){
            opt.call_id = call.id;
            opt.call_type = call.type;
            if(!call.active || call.options.kicked){
                mounted.callFailed();
                return;
            }
            opt._call = call;
            opt.call_loaded = true;
            opt.call_owner = call.owner_id;
            opt.thread_id = call.thread_id;
            opt.thread_type = call.meta.thread_type;
            opt.thread_name = call.meta.thread_name;
            opt.call_admin = call.options.admin;
            opt.created_at = call.created_at;
            opt.INIT_time = moment.now();
            mounted.setJanusInfo(call, false);
            NotifyManager.setTitle(call.meta.thread_name + ' | ' + 'Video Call');
        },
        loadCall : function(thread, call, success){
            Messenger.xhr().request({
                route : Messenger.common().API + 'threads/' + thread + '/calls/' + call,
                success : success,
                fail : mounted.callFailed
            })
        },
        joinConfirm : function(){
            opt.elements.call_status_msg.html('Ready to join with '+opt._call.meta.thread_name+'?');
            opt.elements.call_status_body.html(templates.join_confirm());
            $("#call_join_confirm").one('click', mounted.checkIfJoined);
            $("#call_exit_confirm").one('click', function(){
                window.close();
            });
        },
        checkIfJoined : function() {
            if(!opt._call.options.joined || !opt._call.options.in_call){
                opt.elements.call_status_body.html(Messenger.alert().loader(true))
                Messenger.xhr().payload({
                    route : Messenger.common().API + 'threads/' + opt._call.thread_id + '/calls/' + opt._call.id + '/join',
                    data : {},
                    success : function(){
                        mounted.loadCall(opt._call.thread_id, opt._call.id, function(call){
                            mounted.setJanusInfo(call, true);
                        });
                    },
                    fail : mounted.callFailed,
                    bypass : true,
                    fail_alert : true
                });
            }
            else{
                mounted.ready();
            }
        },
        ready : function(){
            Sockets.heartbeat(false);
            Sockets.setupRTC();
            mounted.setConnections();
            opt.elements.call_status.addClass('NS');
            opt.elements.main_call.removeClass('NS');
            if(opt.call_admin) $("#end_call_nav").removeClass('NS');
            if(opt.call_type === 1){
                window.addEventListener("beforeunload", methods.windowClosed, false);
                window.addEventListener("keydown", methods.checkForRefresh, false);
            }
        },
        setJanusInfo : function(call, reloaded){
            if(!call.options.setup_complete){
                setTimeout(function(){
                    mounted.loadCall(call.thread_id, call.id, mounted.setJanusInfo)
                }, 1500);
                return;
            }
            opt._call = call;
            opt.call_admin = call.options.admin;
            opt.room_id = parseInt(call.options.room_id);
            opt.room_pin = call.options.room_pin;
            if(reloaded){
                mounted.ready();
            }
            else{
                mounted.joinConfirm();
            }
        },
        setConnections : function (delayed) {
            if(!Messenger.common().modules.includes('NotifyManager') || !NotifyManager.sockets().status){
                if(Messenger.format().timeDiffInUnit(moment.now(), opt.INIT_time, 'seconds') >= 8){
                    delayed = true;
                }
                setTimeout(function () {
                    mounted.setConnections(true)
                }, delayed ? 1000 : 0);
                return;
            }
            Sockets.setup();
        },
        callFailed : function(){
            Messenger.alert().Modal({
                allow_close : false,
                size : 'md',
                theme : 'danger',
                centered : true,
                icon : 'video',
                pre_loader : true,
                title : 'Call session not found. Redirecting you...'
            });
            setTimeout(function(){
                window.location.href = Messenger.common().WEB;
            }, 4000);
            setTimeout(function(){
                    window.close();
            }, 3000);
        }
    },
    Sockets = {
        heartbeat : function(check){
            if(opt.call_mode === 4) return;
            let beat = function(){
                if(Messenger.common().modules.includes('NotifyManager') && !NotifyManager.sockets().forced_disconnect){
                    Messenger.xhr().request({
                        route : Messenger.common().API + 'threads/' + opt.thread_id + '/calls/' + opt.call_id + '/heartbeat',
                        success : function(){
                            opt.heartbeat_retries = 0
                        },
                        fail : Sockets.heartbeatFailed
                    })
                }
            };
            beat();
            if(check) return;
            opt.heartbeat_interval = setInterval(beat, 30000)
        },
        heartbeatFailed : function(){
            opt.heartbeat_retries++;
            if(opt.heartbeat_retries < 4) Sockets.heartbeat(true);
            if(opt.heartbeat_retries >= 4){
                clearInterval(opt.heartbeat_interval);
                if(opt.channel) opt.channel.unsubscribe();
                if(Messenger.common().modules.includes('JanusServer')) JanusServer.config().destroy();
                mounted.callFailed();
                setTimeout(function () {
                    window.close()
                }, 3000)
            }
        },
        setup : function(){
            opt.channel = NotifyManager.sockets().Echo.join('messenger.call.'+opt.call_id+'.thread.'+opt.thread_id);
            opt.channel.here(function(users){
                opt.active_profiles = [];
                opt.channel_status = true;
                $.each(users, function() {
                    if(this.provider_id !== Messenger.common().id){
                        opt.active_profiles.push({
                            owner_id : this.provider_id,
                            avatar : this.avatar.sm,
                            name : this.name
                        })
                    }
                })
            })
            .joining(function(user) {
                opt.active_profiles.push({
                    owner_id : user.provider_id,
                    avatar : user.avatar.sm,
                    name : user.name
                });
                Sockets.pushJoin(user)
            })
            .leaving(function(user) {
                for(let i = 0; i < opt.active_profiles.length; i++) {
                    if (opt.active_profiles[i].owner_id === user.provider_id){
                        opt.active_profiles.splice(i, 1);
                        break;
                    }
                }
                Sockets.pushLeave(user)
            })
            .listen('.shutdown', methods.serverShutdownNotice);
        },
        setupRTC : function(){
            if(!Messenger.common().modules.includes('JanusServer')){
                setTimeout(Sockets.setupRTC, 0);
                return;
            }
            JanusServer.config().init(opt.demo);
        },
        pushJoin : function (user) {
            if(Messenger.common().modules.includes('JanusServer')) JanusServer.socket().peerJoin(user);
        },
        pushLeave : function (user) {
            if(Messenger.common().modules.includes('JanusServer')) JanusServer.socket().peerLeave(user);
        },
        disconnected : function () {
            opt.channel_status = false;
            if(Messenger.common().modules.includes('JanusServer')) JanusServer.socket().onDisconnect();
        },
        reconnected : function (full) {
            opt.channel_status = true;
            if(Messenger.common().modules.includes('JanusServer')) JanusServer.socket().onReconnect();
        }
    },
    templates = {
        call_alert : function(data){
            return '<div id="new_call_modal" class="col-12 text-center mb-1"><h4 class="font-weight-bold">'+(data.call.thread_type === 2 ? data.call.thread_name : data.sender.name)+'</h4>' +
                    '<img class="img-fluid rounded" src="'+(data.call.thread_type === 2 ? data.call.thread_avatar.sm : data.sender.avatar.sm)+'" /></div>'
        },
        join_confirm : function() {
            return '<div class="col-12 text-center">' +
                '<button id="call_join_confirm" type="button" title="Join" class="mx-3 shadow-lg btn btn-circle btn-circle-xl btn-success">Join <i class="fas fa-video"></i></button>' +
                '<button id="call_exit_confirm" type="button" title="Exit" class="mx-3 shadow-lg btn btn-circle btn-circle-xl btn-danger">Exit <i class="fas fa-times"></i></button>' +
                '</div>';
        }
    },
    methods = {
        serverShutdownNotice : function(server){
            NotifyManager.sound('notify');
            if(server.shutdown){
                Messenger.alert().Modal({
                    icon : 'power-off',
                    unlock_buttons : false,
                    backdrop_ctrl : false,
                    title : 'Service Notice',
                    theme : 'warning',
                    body : 'Our calls system is going down for maintenance. You have <b>'+server.grace+' minutes</b> ' +
                        'before your session will end automatically. We apologize for any inconvenience. ' +(server.message ? '<br><br>Note: '+server.message : '')
                });
                return;
            }
            Messenger.alert().Modal({
                icon : 'power-off',
                unlock_buttons : false,
                backdrop_ctrl : false,
                title : 'Service Notice',
                theme : 'success',
                body : 'The calls system maintenance was cancelled. You may resume your session as normal.'
            });
        },
        windowClosed : function(){
            if(window.opener){
                window.opener.CallManager.leave(true, {type : 1, id : opt.call_id, thread_id : opt.thread_id})
            }
        },
        checkForRefresh : function(e){
            if(e.key === 'F5' || (e.ctrlKey && e.key === 'r')){
                window.removeEventListener("beforeunload", methods.windowClosed, false)
            }
        },
        updateMessenger : function(call, action){
            if(Messenger.common().modules.includes('ThreadManager')){
                ThreadManager.Import().callStatus(call, action)
            }
        },
        incomingCall : function(call){
            NotifyManager.sound('call');
            methods.updateMessenger(call.call, 'incoming');
            Messenger.alert().Modal({
                wait_for_others : true,
                backdrop_ctrl : false,
                centered : true,
                theme : 'primary',
                icon : 'video',
                size : 'sm',
                title : 'Incoming video call',
                body : templates.call_alert(call),
                cb_btn_txt : 'Answer',
                cb_btn_icon : 'video',
                cb_btn_theme : 'success',
                callback : function(){
                    methods.joinCall(call.call, true)
                },
                cb_close : true,
                timer : 25000
            })
        },
        joinCall : function(call){
            methods.openCallWindow(call);
            methods.updateMessenger(call, 'joined');
        },
        leaveCall : function(parent, call){
            if(!parent){
                opt.processing = true;
                if(opt.heartbeat_interval) clearInterval(opt.heartbeat_interval);
                if(opt.channel_status) opt.channel.unsubscribe();
                if(opt.call_type === 1) window.removeEventListener("beforeunload", methods.windowClosed, false);
            }
            let route;
            if(parent){
               route =  call.thread_id + '/calls/' + call.id + '/leave';
            }
            else{
                route =  opt.thread_id + '/calls/' + opt.call_id + '/leave';
            }
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + route,
                data : {},
                success : function(data){
                    if(parent){
                        if(Messenger.common().modules.includes('ThreadManager')){
                            ThreadManager.Import().callStatus(call, 'left')
                        }
                        NotifyManager.heartbeat();
                        return;
                    }
                    if(window.opener){
                        if(typeof window.opener.ThreadManager !== 'undefined'){
                            window.opener.ThreadManager.Import().callStatus(opt._call, 'left')
                        }
                        if(typeof window.opener.NotifyManager !== 'undefined') window.opener.NotifyManager.heartbeat();
                    }
                    window.close();
                    setTimeout(function () {
                        window.close();
                        window.location.reload()
                    }, 2500)
                },
                fail : function(){
                    if(window.opener) window.close()
                }
            });
        },
        openCallWindow : function(call){
            let popUp = window.open('', call.id);
            if(!popUp || typeof popUp.closed === 'undefined' || popUp.closed ){
                Messenger.alert().destroyModal();
                Messenger.alert().Modal({
                    size : 'md',
                    icon : 'video',
                    backdrop_ctrl : false,
                    title: 'Popup Blocked',
                    theme: 'info',
                    h4 : false,
                    body : '<div class="card"><div class="card-body bg-warning shadow rounded">' +
                        '<h5>It appears your browser is blocking popups. Please allow popups or click the link below to join the call</h5>' +
                        '</div></div><div class="mt-4 col-12 text-center h3 font-weight-bold"><a onclick="Messenger.alert().destroyModal()" target="_blank" href="'+Messenger.common().WEB+'/threads/'+call.thread_id+'/calls/'+call.id+'" ><i class="fas fa-video"></i> Join Call</a></div>'
                });
                return;
            }
            if(popUp.location.href === 'about:blank') popUp.location.href = Messenger.common().WEB + '/threads/'+call.thread_id+'/calls/'+call.id;
            popUp.focus()
        },
        endCall : function(){
            if(!opt.call_admin) return;
            if(opt.heartbeat_interval) clearInterval(opt.heartbeat_interval);
            opt.initialized = false;
            if(opt.call_type === 1){
                window.removeEventListener("beforeunload", methods.windowClosed, false);
            }
            let route = opt.thread_id + '/calls/' + opt.call_id + '/end';
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + route,
                data : {},
                success : function(){
                    if(opt.call_type === 1){
                        window.close();
                        setTimeout(function () {
                            window.location.reload()
                        }, 3500)
                    }
                },
                fail : function(){
                    Messenger.xhr().lockout(false);
                },
                bypass : true
            });
            Messenger.xhr().lockout(true);
        },
        callEnded : function(call){
            methods.updateMessenger(call, 'ended');
            if(opt.initialized && opt.call_type === 1) window.removeEventListener("beforeunload", methods.windowClosed, false);
            if(opt.initialized && call.id === opt.call_id){
                if(opt.heartbeat_interval) clearInterval(opt.heartbeat_interval);
                setTimeout(function () {
                    window.location.reload()
                }, 3500)
            }
            if(window.opener && call.id === opt.call_id && Messenger.common().modules.includes('JanusServer')){
                if(Messenger.common().modules.includes('JanusServer')) JanusServer.config().destroy();
                window.close()
            }
        },
        callLeft : function(call){
            if(opt.initialized) return;
            methods.updateMessenger(call, 'left');
        },
        callJoined : function(call){
            if(opt.initialized) return;
            let modal = $("#new_call_modal");
            methods.updateMessenger(call, 'joined');
            if(modal.length) Messenger.alert().destroyModal();
        },
        popupNoCall : function(){
            Messenger.alert().Alert({
                toast : true,
                theme : 'error',
                title : 'It appears that call/replay is not available or does not exist'
            })
        }
    };
    return {
        init : mounted.Initialize,
        newCall : methods.incomingCall,
        join : methods.joinCall,
        leave : methods.leaveCall,
        joined : methods.callJoined,
        left : methods.callLeft,
        endCall : methods.endCall,
        callEnded : methods.callEnded,
        popupNoCall : methods.popupNoCall,
        setThreadAdmin : function(x){
            opt.thread_admin = x;
        },
        state : function () {
            return {
                initialized : opt.initialized,
                processing : opt.processing,
                call : opt.call,
                call_loaded : opt.call_loaded,
                call_id : opt.call_id,
                call_mode : opt.call_mode,
                call_type : opt.call_type,
                call_owner : opt.call_owner,
                call_admin : opt.call_admin,
                demo : opt.demo,
                room_id : opt.room_id,
                room_pin : opt.room_pin,
                janus_secret : opt.janus_secret,
                janus_debug : opt.janus_debug,
                janus_main : opt.janus_main,
                janus_ice : opt.janus_ice,
                created_at : opt.created_at,
                thread_id : opt.thread_id,
                thread_name : opt.thread_name,
                thread_type : opt.thread_type,
                thread_admin : opt.thread_admin
            }
        },
        channel : function () {
            return {
                socket : opt.channel,
                state : opt.channel_status,
                profiles : opt.active_profiles,
                reconnected : Sockets.reconnected,
                disconnected : Sockets.disconnected
            }
        }
    };
}());