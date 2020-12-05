window.FriendsManager = (function () {
    var opt = {
        lock : true
    },
    Initialize = {
      init : function(){
          opt.lock = false;
      }
    },
    templates = {
        add_to : function(data){
            if("dropdown" in data){
                return '<a class="dropdown-item network_option" onclick="FriendsManager.action({dropdown : true, provider_id : \''+data.provider_id+'\', action : \'add\', provider_alias : \''+data.provider_alias+'\'}); return false;" href="#">' +
                    '<i class="fas fa-user-plus"></i> Add friend</a>';
            }
            return '<button id="add_network_'+data.provider_id+'" data-toggle="tooltip" title="Add friend" data-placement="top" class="btn btn-success pt-1 pb-0 px-2" ' +
                'onclick="FriendsManager.action({action : \'add\', provider_alias : \''+data.provider_alias+'\', provider_id : \''+data.provider_id+'\'});"><i class="fas fa-user-plus fa-2x"></i></button>';
        },
        remove_from : function(data){
            if("dropdown" in data){
                return '<a class="dropdown-item network_option" onclick="FriendsManager.action({dropdown : true, provider_id : \''+data.provider_id+'\', action : \'remove\', provider_alias : \''+data.provider_alias+'\'}); return false;" href="#">' +
                    '<i class="fas fa-user-times"></i> Remove friend</a>';
            }
            return '<button id="remove_network_'+data.provider_id+'" data-toggle="tooltip" title="Remove friend" data-placement="top" class="btn btn-danger pt-1 pb-0 px-2" ' +
                'onclick="FriendsManager.action({action : \'remove\', provider_alias : \''+data.party.provider_alias+'\', provider_id : \''+data.party.provider_id+'\'});"><i class="fas fa-user-times fa-2x"></i></button>';
        },
        cancel_request : function(data){
            if("dropdown" in data){
                return '<a class="dropdown-item network_option" onclick="FriendsManager.action({dropdown : true, provider_id : \''+data.recipient.provider_id+'\', action : \'cancel\', sent_friend_id : \''+data.id+'\'}); return false;" href="#">' +
                    '<i class="fas fa-ban"></i> Cancel friend request</a>';
            }
            return '<button id="cancel_network_'+data.provider_id+'" data-toggle="tooltip" title="Cancel friend request" data-placement="top" class="btn btn-danger pt-1 pb-0 px-2" ' +
                'onclick="FriendsManager.action({provider_id : \''+data.recipient.provider_id+'\', action : \'cancel\', sent_friend_id : \''+data.id+'\'});"><i class="fas fa-ban fa-2x"></i></button>';
        }
    },
    methods = {
        perform : function(arg){
            if(opt.lock) return;
            Messenger.button().addLoader({id : '#'+arg.action+'_network_'+arg.provider_id});

            switch (arg.action){
                case 'add':
                    methods.addFriend(arg);
                break;
                case 'remove':
                    methods.removeFriend(arg);
                break;
                case 'cancel':
                    methods.cancelFriend(arg);
                break;
                case 'accept':
                    methods.acceptFriend(arg);
                break;
                case 'deny':
                    methods.denyFriend(arg);
                break;
            }
        },
        addFriend : function(arg){
            Messenger.xhr().payload({
                route : Messenger.common().API + 'friends/sent',
                data : {
                    recipient_id : arg.provider_id,
                    recipient_alias : arg.provider_alias
                },
                shared : arg,
                success : function(sent){
                    let elm = $("#network_for_"+sent.recipient.provider_id);
                    if(elm.length) elm.html(templates.cancel_request(sent));
                    Messenger.alert().Alert({
                        title : 'Friends',
                        body : 'Friend request sent to ' + sent.recipient.name + '!',
                        toast : true,
                        theme : 'success'
                    });
                    PageListeners.listen().tooltips();
                },
                fail_alert : true
            });
        },
        acceptFriend : function(arg){
            Messenger.xhr().payload({
                route : Messenger.common().API + 'friends/pending/' + arg.pending_friend_id,
                data : {},
                shared : arg,
                success : function(friend){
                    let elm = $("#network_for_"+friend.party.provider_id);
                    if(elm.length) elm.html(templates.remove_from(friend));
                    Messenger.alert().Alert({
                        title : 'Friends',
                        body : 'Approved the friend request from ' + friend.party.name + '!',
                        toast : true,
                        theme : 'success'
                    });
                    PageListeners.listen().tooltips();
                    NotifyManager.friendsPending()
                },
                fail_alert : true
            }, 'put');
        },
        cancelFriend : function(arg){
            Messenger.xhr().payload({
                route : Messenger.common().API + 'friends/sent/' + arg.sent_friend_id,
                data : {},
                shared : arg,
                success : function(provider){
                    let elm = $("#network_for_"+provider.provider_id);
                    if(elm.length) elm.html(templates.add_to(provider));
                    Messenger.alert().Alert({
                        title : 'Friends',
                        body : 'Cancelled friend request to ' + provider.name + '.',
                        toast : true,
                        theme : 'warning'
                    });
                    PageListeners.listen().tooltips();
                    NotifyManager.sentFriends()
                },
                fail_alert : true
            }, 'delete');
        },
        denyFriend : function(arg){
            Messenger.xhr().payload({
                route : Messenger.common().API + 'friends/pending/' + arg.pending_friend_id,
                data : {},
                shared : arg,
                success : function(provider){
                    let elm = $("#network_for_"+provider.provider_id);
                    if(elm.length) elm.html(templates.add_to(provider));
                    Messenger.alert().Alert({
                        title : 'Friends',
                        body : 'Denied friend request from ' + provider.name + '.',
                        toast : true,
                        theme : 'error'
                    });
                    PageListeners.listen().tooltips();
                    NotifyManager.friendsPending()
                },
                fail_alert : true
            }, 'delete');
        },
        removeFriend : function(arg){
            Messenger.xhr().payload({
                route : Messenger.common().API + 'friends/' + arg.friend_id,
                data : {},
                shared : arg,
                success : function(provider){
                    let elm = $("#network_for_"+provider.provider_id);
                    if(elm.length) elm.html(templates.add_to(provider));
                    Messenger.alert().Alert({
                        title : 'Friends',
                        body : 'Removed ' + provider.name + ' from your friends.',
                        toast : true,
                        theme : 'error'
                    });
                    PageListeners.listen().tooltips();
                },
                fail_alert : true
            }, 'delete');
        }
    };
    return {
        action : methods.perform,
        init : Initialize.init,
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());