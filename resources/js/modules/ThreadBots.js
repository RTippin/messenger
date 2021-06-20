window.ThreadBots = (function () {
    var opt = {
        lock : true,
        thread : null,
        bots_table : null,
        current_bot : null,
        current_bot_action_container : null,
        avatar_input : null,
        handlers : null,
        current_handler : null,
        current_action : null,
    },
    mounted = {
        Initialize : function () {
            opt.lock = false;
            $('body').append(templates.avatar_input());
            opt.avatar_input = document.getElementById('bot_avatar_upload');
            opt.avatar_input.addEventListener('change', methods.uploadAvatar, false);
        }
    },
    methods = {
        setThread : function () {
            if (ThreadManager.state()._thread) {
                opt.thread = ThreadManager.state()._thread;
                return true;
            }
            return false;
        },
        loadDataTable : function(elm, term){
            if(opt.bots_table) opt.bots_table.destroy();
            if(!elm || !elm.length) return;
            opt.bots_table = elm.DataTable({
                "language": {
                    "info": "Showing _START_ to _END_ of _TOTAL_ "+term,
                    "lengthMenu": "Show _MENU_ "+term,
                    "infoEmpty": "Showing 0 to 0 of 0 "+term,
                    "infoFiltered": "(filtered from _MAX_ total "+term+")",
                    "emptyTable": "No "+term+" found",
                    "zeroRecords": "No matching "+term+" found"
                },
                "drawCallback": function(settings){
                    let api = new $.fn.DataTable.Api(settings), pagination = $(this).closest('.dataTables_wrapper').find('.dataTables_paginate');
                    pagination.toggle(api.page.info().pages > 1);
                },
                "pageLength": 25
            });
        },
        viewBots : function(){
            if (!methods.setThread()) return;
            let gather = () => {
                Messenger.xhr().request({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots',
                    success : function(bots){
                        Messenger.alert().fillModal({
                            body : templates.bots(bots),
                            title : opt.thread.name+' Bots'
                        });
                        methods.loadDataTable($("#view_bots_table"), 'bots')
                    },
                    fail_alert : true
                })
            };
            Messenger.alert().Modal({
                icon : 'robot',
                backdrop_ctrl : false,
                theme : 'dark',
                title : 'Loading Bots...',
                pre_loader : true,
                overflow : true,
                unlock_buttons : false,
                h4 : false,
                size : 'lg',
                onReady : gather
            });
        },
        addBot : function(){
            Messenger.alert().Modal({
                icon : 'robot',
                backdrop_ctrl : false,
                theme : 'dark',
                title : 'Add Bot',
                overflow : true,
                unlock_buttons : false,
                h4 : false,
                size : 'md',
                body : templates.add_bot(),
                cb_btn_txt : 'Add Bot',
                cb_btn_icon : 'robot',
                cb_btn_theme : 'success',
                onReady : function(){
                    $(".m_setting_toggle").change(function(){
                        $(this).is(':checked') ? $(this).closest('tr').addClass('alert-success') : $(this).closest('tr').removeClass('alert-success')
                    })
                },
                callback : methods.storeBot
            });
        },
        viewBot : function(id) {
            if (!methods.setThread()) return;
            let gather = () => {
                Messenger.xhr().request({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+id,
                    success : function(bot){
                        opt.current_bot = bot;
                        Messenger.alert().fillModal({
                            body : templates.view_bot(bot),
                            title : bot.name
                        });
                        opt.current_bot_action_container = $("#bot_actions_container");
                        if (bot.hasOwnProperty('actions')) {
                            setTimeout(function(){
                                methods.loadDataTable($("#view_bots_actions_table"), 'actions')
                            }, 100);
                        }
                    },
                    fail_alert : true
                })
            };
            Messenger.alert().Modal({
                icon : 'robot',
                backdrop_ctrl : false,
                theme : 'dark',
                title : 'Loading Bot...',
                pre_loader : true,
                unlock_buttons : false,
                h4 : false,
                size : 'fullscreen',
                onReady : gather
            });
        },
        editBot : function(id){
            if (!methods.setThread()) return;
            let gather = () => {
                let fill = (bot) => {
                    opt.current_bot = bot;
                    Messenger.alert().fillModal({
                        body : templates.edit_bot(bot),
                        title : 'Editing '+bot.name
                    });
                    $(".m_setting_toggle").change(function(){
                        $(this).is(':checked') ? $(this).closest('tr').addClass('alert-success') : $(this).closest('tr').removeClass('alert-success')
                    })
                }
                if(opt.current_bot && opt.current_bot.id === id){
                    fill(opt.current_bot);
                    return;
                }
                Messenger.xhr().request({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+id,
                    success : fill,
                    fail_alert : true,
                    fail_keep_open : true,
                })
            };
            Messenger.alert().Modal({
                icon : 'robot',
                backdrop_ctrl : false,
                theme : 'dark',
                title : 'Loading Bot...',
                pre_loader : true,
                unlock_buttons : false,
                h4 : false,
                size : 'md',
                overflow : true,
                onReady : gather,
                cb_btn_txt : 'Save Bot',
                cb_btn_icon : 'robot',
                cb_btn_theme : 'success',
                callback : function(){
                    methods.updateBot(id)
                }
            });
        },
        editAction : function(id){
            if (!methods.setThread() || !opt.current_bot) return;
            if(!opt.current_bot.hasOwnProperty('actions') || !opt.current_bot.actions.length){
                methods.reloadBotActions();
            }
            for(let i = 0; i < opt.current_bot.actions.length; i++) {
                if (opt.current_bot.actions[i].id === id) {
                    opt.current_action = opt.current_bot.actions[i];
                    return methods.generateEditActionForm();
                }
            }
            methods.reloadBotActions();
        },
        updateAction : function(){
            if (!methods.setThread() || !opt.current_bot || !opt.current_action) return;
            Messenger.button().addLoader({id : '#save_bot_action_btn'});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+opt.current_bot.id+'/actions/'+opt.current_action.id,
                data : methods.makeHandlerFormData(opt.current_action.handler),
                success : methods.reloadBotActions,
                fail_alert : true,
                fail_keep_open : true,
            }, 'put');
        },
        generateEditActionForm : function(){
            let extra = '';
            switch(opt.current_action.handler.alias){
                case 'react':
                    extra = handlers.reaction(opt.current_action.payload.reaction);
                break;
                case 'reply':
                    extra = handlers.replies(opt.current_action.payload);
                break;
            }
            opt.current_bot_action_container.html(
                handlers.start(opt.current_action.handler, true) +
                handlers.base(opt.current_action) +
                handlers.triggers(opt.current_action.triggers, opt.current_action.handler.triggers) +
                handlers.match(opt.current_action.match, opt.current_action.handler.match) +
                extra +
                handlers.end(true)
            );
            $(".m_setting_toggle").change(function(){
                $(this).is(':checked') ? $(this).closest('tr').addClass('alert-success') : $(this).closest('tr').removeClass('alert-success')
            })
        },
        viewAvailableHandlers : function(){
            if (!methods.setThread() || !opt.current_bot) return;
            opt.current_bot_action_container.html(Messenger.alert().loader(true));
            Messenger.xhr().request({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+opt.current_bot.id+'/add-handlers',
                success : function(handlers){
                    opt.handlers = handlers;
                    opt.current_bot_action_container.html(templates.view_handlers(handlers));
                    methods.loadDataTable($("#view_handlers_table"), 'actions')
                },
                fail_alert : true
            })
        },
        createAction : function(alias){
            if (!methods.setThread() || !opt.current_bot) return;
            if(!opt.handlers){
                Messenger.xhr().request({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+opt.current_bot.id+'/add-handlers',
                    success : function(handlers){
                        opt.handlers = handlers;
                        methods.createAction(alias)
                    },
                    fail_alert : true
                });
                return;
            }
            for(let i = 0; i < opt.handlers.length; i++) {
                if (opt.handlers[i].alias === alias) {
                    opt.current_handler = opt.handlers[i];
                    return methods.generateCreateActionForm();
                }
            }
            methods.reloadBotActions();
        },
        generateCreateActionForm : function(){
            let extra = '';
            switch(opt.current_handler.alias){
                case 'react':
                    extra = handlers.reaction();
                break;
                case 'reply':
                    extra = handlers.replies();
                break;
            }
            opt.current_bot_action_container.html(
                handlers.start(opt.current_handler, false) +
                handlers.base() +
                handlers.triggers(null, opt.current_handler.triggers) +
                handlers.match(null, opt.current_handler.match) +
                extra +
                handlers.end(false)
            );
            $(".m_setting_toggle").change(function(){
                $(this).is(':checked') ? $(this).closest('tr').addClass('alert-success') : $(this).closest('tr').removeClass('alert-success')
            })
        },
        storeAction : function(){
            if (!methods.setThread() || !opt.current_handler || !opt.current_bot) return;
            Messenger.button().addLoader({id : '#save_bot_action_btn'});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+opt.current_bot.id+'/actions',
                data : methods.makeHandlerFormData(opt.current_handler),
                success : methods.reloadBotActions,
                fail_alert : true,
                fail_keep_open : true,
            });
        },
        reloadBotActions : function(){
            if (!methods.setThread() || !opt.current_bot) return;
            opt.current_bot_action_container.html(Messenger.alert().loader(true));
            Messenger.xhr().request({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+opt.current_bot.id+'/actions',
                success : function(actions){
                    opt.current_bot.actions = actions;
                    opt.current_bot_action_container.html(templates.bot_actions_table(actions));
                    methods.loadDataTable($("#view_bots_actions_table"), 'actions')
                },
                fail_alert : true
            })
        },
        makeHandlerFormData : function(handler){
            let form = {};
            form.handler = handler.alias;
            form.cooldown = $("#g_s_bot_cooldown").val();
            form.enabled = $("#g_s_action_enabled").is(":checked");
            form.admin_only = $("#g_s_admin_only_action").is(":checked");
            if(!handler.triggers){
                form.triggers = [$("#g_s_action_triggers").val()];
            }
            if(!handler.match){
                form.match = $("#g_s_action_match").val();
            }
            if(handler.alias === 'react'){
                form.reaction = $("#g_s_bot_reaction").val();
            }
            if(handler.alias === 'reply'){
                form.quote_original = $("#g_s_quote_original").is(":checked");
                form.replies = [];
                let replies = [
                    $("#g_s_reply_1").val(),
                    $("#g_s_reply_2").val(),
                    $("#g_s_reply_3").val(),
                    $("#g_s_reply_4").val(),
                    $("#g_s_reply_5").val()
                ];
                replies.forEach((reply) => {
                    if(reply.trim().length) form.replies.push(reply)
                });
            }
            return form;
        },
        removeBot : function(id){
            if(!methods.setThread()) return;
            if(opt.current_bot && opt.current_bot.id === id){
                Messenger.alert().Modal({
                    theme : 'danger',
                    icon : 'trash',
                    backdrop_ctrl : false,
                    title : 'Remove Bot?',
                    body : templates.warn_delete(),
                    cb_btn_txt : 'Delete',
                    cb_btn_icon : 'trash',
                    cb_btn_theme : 'danger',
                    callback : methods.deleteBot
                })
                return;
            }
            Messenger.xhr().request({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+id,
                success : function(bot){
                    opt.current_bot = bot;
                    methods.removeBot(bot.id)
                },
                fail_alert : true
            })
        },
        removeAction : function(id){
            if(!methods.setThread() || !opt.current_bot) return;
            opt.current_bot_action_container.html(Messenger.alert().loader(true));
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+opt.current_bot.id+'/actions/'+id,
                data : {},
                success : methods.reloadBotActions,
                fail_alert : true
            }, 'delete');
        },
        deleteBot : function(){
            if(!methods.setThread() || !opt.current_bot) return;
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+opt.current_bot.id,
                data : {},
                success : methods.viewBots,
                fail_alert : true
            }, 'delete');
        },
        storeBot : function(){
            if (!methods.setThread()) return;
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots',
                data : {
                    name : $('#g_s_bot_name').val(),
                    enabled : $("#g_s_bot_enabled").is(":checked"),
                    hide_actions : $("#g_s_hide_actions").is(":checked"),
                    cooldown : $("#g_s_bot_cooldown").val(),
                },
                success : function(bot){
                    methods.viewBot(bot.id)
                },
                fail_alert : true,
                fail_keep_open : true,
            });
        },
        updateBot : function(id){
            if (!methods.setThread()) return;
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+id,
                data : {
                    name : $('#g_s_bot_name').val(),
                    enabled : $("#g_s_bot_enabled").is(":checked"),
                    hide_actions : $("#g_s_hide_actions").is(":checked"),
                    cooldown : $("#g_s_bot_cooldown").val(),
                },
                success : function(data){
                    methods.viewBot(data.id)
                },
                fail_alert : true,
                fail_keep_open : true,
            }, 'put');
        },
        uploadAvatar : function () {
            if(!methods.setThread() || !opt.current_bot || !opt.avatar_input.files.length) return;
            let data = new FormData();
            data.append('image', opt.avatar_input.files[0]);
            PageListeners.listen().disposeTooltips();
            Messenger.alert().fillModal({loader : true, no_close : true, body : null, title : 'Uploading...'});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+opt.current_bot.id+'/avatar',
                data : data,
                success : function(data){
                    methods.viewBot(data.id)
                },
                fail_alert : true
            });
        },
        removeAvatar : function(){
            if(!methods.setThread() || !opt.current_bot) return;
            Messenger.alert().fillModal({loader : true, no_close : true, body : null, title : 'Removing...'});
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+opt.current_bot.id+'/avatar',
                data : {},
                success : function(data){
                    methods.viewBot(data.id)
                },
                fail_alert : true
            }, 'delete');
        }
    },
    templates = {
        bots : function(bots){
            let table_top = '<div class="row">\n' +
                '    <div class="col-12">\n' +
                '        <div class="table-responsive-sm">\n' +
                '            <table id="view_bots_table" class="table table-sm table-hover">\n' +
                '                <thead>\n' +
                '                <tr>\n' +
                '                    <th>Name</th>\n' +
                '                    <th>Enabled</th>\n' +
                '                    <th>On Cooldown</th>\n' +
                '                    <th>Cooldown</th>\n' +
                '                    <th>Actions</th>\n' +
                '                    <th>Options</th>\n' +
                '                </tr>\n' +
                '                </thead>\n' +
                '                <tbody>',
                table_bot = '</tbody></table></div></div></div>',
                table_fill = '';
            let bot_fill = (bot) => {
                let online = bot.enabled ? (bot.on_cooldown ? 'away' : 'online') : 'offline',
                    manage = '<a class="dropdown-item" onclick="ThreadBots.editBot(\''+bot.id+'\'); return false;" href="#" title="Edit"><i class="fas fa-edit"></i> Edit</a>' +
                        '<a class="dropdown-item" onclick="ThreadBots.removeBot(\''+bot.id+'\'); return false;" href="#" title="Remove"><i class="fas fa-trash-alt"></i> Delete</a>';
                return '<tr id="row_'+bot.id+'">\n' +
                    '     <td class="pointer_area" onclick="ThreadBots.viewBot(\''+bot.id+'\')">\n' +
                    '      <div class="table_links">\n' +
                    '        <div class="nowrap">\n' +
                    '          <img alt="Bot Avatar" class="rounded-circle group-image avatar-is-'+online+'" src="'+bot.avatar.sm+'" />\n' +
                    '          <span class="h5"><span class="badge badge-light">'+bot.name+'</span></span>\n' +
                    '         </div>\n' +
                    '       </div>\n' +
                    '  </td>'+
                    '  <td class="h5">\n' +
                          (bot.enabled ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times"></i></span>') +
                    '  </td>\n' +
                    '  <td class="h5">\n' +
                          (bot.on_cooldown ? '<span class="badge badge-danger"><i class="fas fa-hourglass-half"></i> Yes</span>' : '<span class="badge badge-success"><i class="fas fa-check"></i> No</span>') +
                    '  </td>\n' +
                    '  <td class="h5"><span class="badge badge-primary">'+bot.cooldown+' seconds</span></td>\n' +
                    '  <td class="h5"><span class="badge badge-primary">'+bot.actions_count+'</span></td>\n' +
                    '  <td>\n' +
                    '  <div class="dropdown float-right">\n' +
                    '    <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown"><i class="fas fa-cog"></i></button>\n' +
                    '    <div class="dropdown-menu dropdown-menu-right">\n' +
                    '       <a class="dropdown-item" onclick="ThreadBots.viewBot(\''+bot.id+'\'); return false;" href="#" title="View"><i class="fas fa-robot"></i> '+(opt.thread.options.manage_bots ? 'Manage Actions' : 'View Actions')+'</a>' +
                            (opt.thread.options.manage_bots ? manage : '') +
                    ' </span>\n' +
                    ' </div></div>' +
                    '</td>\n' +
                    '</tr>'
            };
            if(bots && bots.length){
                bots.forEach((bot) => { table_fill += bot_fill(bot)})
            }
            return table_top+table_fill+table_bot
        },
        add_bot : function(){
            return '<form id="new_bot_form" action="">\n' +
                '<div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '    <h5>Bot Name:</h5>' +
                '    <div class="input-group input-group-lg col-12 mb-0">\n' +
                '        <div class="input-group-prepend">\n' +
                '            <span class="input-group-text"><i class="fas fa-robot"></i></span>\n' +
                '         </div>\n' +
                '         <input autocomplete="off" minlength="2" class="form-control font-weight-bold shadow-sm" id="g_s_bot_name" placeholder="Bot Name" name="bot-name-'+Date.now()+'" required>' +
                '     </div>\n' +
                '</div>' +
                '<hr>' +
                '<div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '    <h5>Cooldown (in seconds):</h5>' +
                '    <div class="input-group input-group-lg col-12 mb-0">\n' +
                '        <div class="input-group-prepend">\n' +
                '            <span class="input-group-text"><i class="fas fa-clock"></i></span>\n' +
                '         </div>\n' +
                '         <input type="number" autocomplete="off" min="0" max="900" class="form-control font-weight-bold shadow-sm" id="g_s_bot_cooldown" placeholder="Bot Cooldown" name="bot-cooldown-'+Date.now()+'" required value="0">' +
                '     </div>\n' +
                '</div>'+
                '    <hr>\n' +
                '    <div class="form-row mx-n2 rounded bg-light text-dark pb-3 pt-2 px-3 shadow-sm">\n' +
                '        <label class="font-weight-bold h5 control-label" for="g_s_table">Bot Toggles:</label>\n' +
                '        <table id="g_s_table" class="table mb-0 table-sm table-hover">\n' +
                '            <tbody>\n' +
                '            <tr class="alert-success">\n' +
                '                <td class="pointer_area" onclick="$(\'#g_s_bot_enabled\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Enabled</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_bot_enabled" name="g_s_bot_enabled" type="checkbox" checked>\n' +
                '                        <label for="g_s_bot_enabled"></label>\n' +
                '                    </span></div>\n' +
                '                </td>\n' +
                '            </tr>\n' +
                '            <tr>\n' +
                '                <td class="pointer_area" onclick="$(\'#g_s_hide_actions\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Hide Actions</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_hide_actions" name="g_s_hide_actions" type="checkbox">\n' +
                '                        <label for="g_s_hide_actions"></label>\n' +
                '                    </span></div>\n' +
                '                </td>\n' +
                '            </tr>\n' +
                '            </tbody>\n' +
                '        </table>\n' +
                '    </div>\n' +
                '</form>';
        },
        edit_bot : function(bot){
            return '<form id="edit_bot_form" action="">\n' +
                '<div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '    <h5 class="font-weight-bold">Bot Name:</h5>' +
                '    <div class="input-group input-group-lg col-12 mb-0">\n' +
                '        <div class="input-group-prepend">\n' +
                '            <span class="input-group-text"><i class="fas fa-robot"></i></span>\n' +
                '         </div>\n' +
                '         <input autocomplete="off" minlength="2" class="form-control font-weight-bold shadow-sm" id="g_s_bot_name" placeholder="Bot Name" name="bot-name-'+Date.now()+'" required value="'+bot.name+'">' +
                '     </div>\n' +
                '</div>' +
                '<hr>' +
                '<div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '    <h5 class="font-weight-bold">Cooldown [in seconds]:</h5>' +
                '    <div class="input-group input-group-lg col-12 mb-0">\n' +
                '        <div class="input-group-prepend">\n' +
                '            <span class="input-group-text"><i class="fas fa-clock"></i></span>\n' +
                '         </div>\n' +
                '         <input type="number" autocomplete="off" min="0" max="900" class="form-control font-weight-bold shadow-sm" id="g_s_bot_cooldown" placeholder="Bot Cooldown" name="bot-cooldown-'+Date.now()+'" required value="'+bot.cooldown+'">' +
                '     </div>\n' +
                '</div>'+
                '    <hr>\n' +
                '    <div class="form-row mx-n2 rounded bg-light text-dark pb-3 pt-2 px-3 shadow-sm">\n' +
                '        <label class="font-weight-bold h5 control-label" for="g_s_table">Bot Toggles:</label>\n' +
                '        <table id="g_s_table" class="table mb-0 table-sm table-hover">\n' +
                '            <tbody>\n' +
                '            <tr class="'+(bot.enabled ? 'alert-success' : '')+'">\n' +
                '                <td class="pointer_area" onclick="$(\'#g_s_bot_enabled\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Enabled</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_bot_enabled" name="g_s_bot_enabled" type="checkbox" '+(bot.enabled ? 'checked' : '')+'>\n' +
                '                        <label for="g_s_bot_enabled"></label>\n' +
                '                    </span></div>\n' +
                '                </td>\n' +
                '            </tr>\n' +
                '            <tr class="'+(bot.hide_actions ? 'alert-success' : '')+'">\n' +
                '                <td class="pointer_area" onclick="$(\'#g_s_hide_actions\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Hide Actions</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_hide_actions" name="g_s_hide_actions" type="checkbox" '+(bot.hide_actions ? 'checked' : '')+'>\n' +
                '                        <label for="g_s_hide_actions"></label>\n' +
                '                    </span></div>\n' +
                '                </td>\n' +
                '            </tr>\n' +
                '            </tbody>\n' +
                '        </table>\n' +
                '    </div>\n' +
                '    <hr>\n' +
                '    <div class="form-group mb-0 py-2 alert-dark shadow rounded">\n' +
                '    <div class="col-12">' +
                '        <img alt="Avatar" height="62" width="62" class="mr-3 rounded avatar-is-offline" src="'+bot.avatar.sm+'"/>' +
                '        <button onclick="$(\'#bot_avatar_upload\').click()" type="button" class="btn btn-sm btn-success mr-3"><i class="fas fa-image"></i> Upload Avatar</button>' +
                '        <button onclick="ThreadBots.removeAvatar()" type="button" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Remove Avatar</button>' +
                '    </div></div>\n' +
                '    <hr>\n' +
                '    <div class="text-center form-group mb-0 py-2 alert-danger shadow rounded">\n' +
                '        <div class="mb-1 font-weight-bold">You will be asked to confirm this action</div>\n' +
                '        <button onclick="ThreadBots.removeBot(\''+bot.id+'\')" type="button" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Remove Bot</button>\n' +
                '    </div>\n' +
                '</form>';
        },
        view_bot : function(bot){
            let online = bot.enabled ? (bot.on_cooldown ? 'away' : 'online') : 'offline',
                actions = '<div class="col-12 mt-5 text-center h2"><span class="badge badge-light"><i class="fas fa-eye-slash"></i> Actions are hidden</span></div>',
                editable = '<hr class="mt-2"><div class="row"><div class="col-12 text-center">' +
                    '<button onclick="ThreadBots.editBot(\''+bot.id+'\')" type="button" class="btn btn-sm btn-outline-success mr-3 mb-2">Edit Bot <i class="fas fa-edit"></i></button>' +
                    '<button onclick="ThreadBots.viewAvailableHandlers()" type="button" class="btn btn-sm btn-outline-success mr-3 mb-2">Add Actions <i class="fas fa-server"></i></button>' +
                    '<button class="btn btn-sm btn-outline-success dropdown-toggle mr-3 mb-2" type="button" id="botAvatarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                    '  Bot Avatar <i class="fas fa-image"></i>' +
                    '</button>\n' +
                    '  <div class="dropdown-menu" aria-labelledby="botAvatarDropdown">\n' +
                    '    <a onclick="$(\'#bot_avatar_upload\').click(); return false;" class="dropdown-item" href="#"><i class="fas fa-image"></i> Upload Avatar</a>\n' +
                    '    <a onclick="ThreadBots.removeAvatar(); return false;" class="dropdown-item" href="#"><i class="fas fa-trash"></i> Remove Avatar</a>\n' +
                    '  </div>\n' +
                    '<button onclick="ThreadBots.removeBot(\''+bot.id+'\')" type="button" class="btn btn-sm btn-outline-danger mr-3 mb-2">Delete Bot <i class="fas fa-trash-alt"></i></button>' +
                    '</div></div><hr>';
            if (bot.hasOwnProperty('actions')) {
                actions = templates.bot_actions_table(bot.actions);
            }

            return '<div class="row">' +
                '<div class="col-12 col-md-6 mb-3">' +
                '<img alt="Avatar" height="75" width="75" class="float-left mr-3 rounded avatar-is-'+online+'" src="'+bot.avatar.md+'"/>' +
                '<h3 class="font-weight-bold">'+bot.name+'</h3>' +
                '<h5>Creator: '+bot.owner.name+'</h4>' +
                '</div>' +
                '<div class="col-12 col-md-6 h5">' +
                '<div class="float-right">' +
                '<div class="col-12 mb-2">Enabled : ' + (bot.enabled ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times"></i></span>') + '</div>' +
                '<div class="col-12 mb-2">Cooldown : <span class="badge badge-primary mr-3">'+bot.cooldown+' seconds</span></div>' +
                '<div class="col-12">On cooldown? ' + (bot.on_cooldown ? '<span class="badge badge-danger"><i class="fas fa-hourglass-half"></i> Yes</span>' : '<span class="badge badge-success"><i class="fas fa-check"></i> No</span>') + '</div>' +
                '</div></div></div>' +
                (opt.thread.options.manage_bots ? editable : '') +
                '<div id="bot_actions_container">' + actions + '</div>';
        },
        bot_actions_table : function(actions){
            let table_top = '<div class="row">\n' +
                '    <div class="col-12">\n' +
                '        <div class="table-responsive-xl">\n' +
                '            <table id="view_bots_actions_table" class="table table-sm table-hover">\n' +
                '                <thead>\n' +
                '                <tr>\n' +
                '                    <th>Name</th>\n' +
                '                    <th>Enabled</th>\n' +
                '                    <th>Description</th>\n' +
                '                    <th>Triggers</th>\n' +
                '                    <th>Match</th>\n' +
                '                    <th>Payload</th>\n' +
                '                    <th>On Cooldown</th>\n' +
                '                    <th>Cooldown</th>\n' +
                '                    <th>Admin Only</th>\n' +
                                    (opt.thread.options.manage_bots ? ' <th>Options</th>' : '') +
                '                </tr>\n' +
                '                </thead>\n' +
                '                <tbody>',
                table_bot = '</tbody></table></div></div></div>',
                table_fill = '';
            let action_fill = (action) => {
                let triggers = '',
                    options = '<td><div class="dropdown float-right">' +
                        '<button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown"><i class="fas fa-cog"></i></button>' +
                        '    <div class="dropdown-menu dropdown-menu-right">' +
                        '       <a class="dropdown-item" onclick="ThreadBots.editAction(\''+action.id+'\'); return false;" href="#" title="Edit"><i class="fas fa-edit"></i> Edit</a>' +
                        '       <a class="dropdown-item" onclick="ThreadBots.removeAction(\''+action.id+'\'); return false;" href="#" title="Delete"><i class="fas fa-trash-alt"></i> Delete</a>' +
                        '</span></div></div></td>';
                action.triggers.forEach((trigger) => triggers += '<span class="badge badge-light mr-1">'+trigger+'</span>');
                return '<tr id="row_'+action.id+'">\n' +
                    '  <td class="h5 nowrap">'+action.handler.name+'</td>'+
                    '  <td class="h5">\n' +
                          (action.enabled ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times"></i></span>') +
                    '  </td>\n' +
                    '  <td class="h6">'+action.handler.description+'</td>'+
                    '  <td class="h5">'+triggers+'</td>'+
                    '  <td class="h5"><span class="badge badge-primary">'+action.match+'</span></td>'+
                    '  <td class="h5 nowrap action-payload">'+templates.handler_payload(action)+'</td>'+
                    '  <td class="h5">\n' +
                           (action.on_cooldown ? '<span class="badge badge-danger"><i class="fas fa-hourglass-half"></i> Yes</span>' : '<span class="badge badge-success"><i class="fas fa-check"></i> No</span>') +
                    '  </td>\n' +
                    '  <td class="h5"><span class="badge badge-primary">'+action.cooldown+' seconds</span></td>\n' +
                    '  <td class="h5">\n' +
                           (action.admin_only ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times"></i></span>') +
                    '  </td>\n' +
                    (opt.thread.options.manage_bots ? options : '') +
                    '</tr>'
            };
            if(actions && actions.length){
                actions.forEach((action) => { table_fill += action_fill(action)})
            }
            return table_top+table_fill+table_bot
        },
        handler_payload : function(action){
            switch(action.handler.alias){
                case 'react':
                    return Messenger.format().shortcodeToImage(action.payload.reaction);
                case 'reply':
                    let replies = '<ul class="p-0 my-0 mr-0 ml-1">';
                    action.payload.replies.forEach((reply) => replies += '<li>'+Messenger.format().shortcodeToImage(reply)+'</li>');
                    return replies + '</ul>';
            }
            return 'N/A';
        },
        view_handlers : function(handlers){
            let table_top = '<div class="col-12 my-3 text-center h2"><span class="badge badge-light"><i class="fas fa-list"></i> Select an action:</span></div>' +
                '<div class="row">\n' +
                '    <div class="col-12">\n' +
                '        <div class="table-responsive-xl">\n' +
                '            <table id="view_handlers_table" class="table table-sm table-hover">\n' +
                '                <thead>\n' +
                '                <tr>\n' +
                '                    <th>Name</th>\n' +
                '                    <th>Description</th>\n' +
                '                    <th>Unique</th>\n' +
                '                    <th>Preset Triggers</th>\n' +
                '                    <th>Preset Match</th>\n' +
                '                    <th>Options</th>\n' +
                '                </tr>\n' +
                '                </thead>\n' +
                '                <tbody>',
                table_fill = '',
                table_bot = '</tbody></table></div></div></div>' +
                    '<hr><div class="col-12 text-center">' +
                    '<button onclick="ThreadBots.reloadBotActions()" type="button" class="btn btn-info">Cancel <i class="fas fa-undo"></i></button>' +
                    '</div>';
            let handler_fill = (handler) => {
                let triggers = 'N\A';
                if(handler.triggers && handler.triggers.length){
                    triggers = '';
                    handler.triggers.forEach((trigger) => triggers += '<span class="badge badge-light mr-1">'+trigger+'</span>');
                }
                return '<tr class="pointer_area" onclick="ThreadBots.createAction(\''+handler.alias+'\')">\n' +
                    '  <td class="h5 nowrap">'+handler.name+'</td>'+
                    '  <td class="h6">'+handler.description+'</td>'+
                    '  <td class="h5">\n' +
                          (handler.unique ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times"></i></span>') +
                    '  </td>\n' +
                    '  <td class="h5 nowrap">'+triggers+'</td>'+
                    '  <td class="h5">'+(handler.match ?? 'N/A')+'</td>'+
                    '  <td class="h5"><button type="button" class="btn btn-sm btn-primary">Select <i class="fas fa-server"></i></button></td>'+
                    '</tr>'
            };
            if(handlers && handlers.length){
                handlers.forEach((handler) => { table_fill += handler_fill(handler)})
            }
            return table_top+table_fill+table_bot
        },
        warn_delete : function(){
          return 'Really Delete <strong>'+opt.current_bot.name+'</strong>?'+
              '<div class="card mt-3"><div class="card-body bg-warning shadow rounded"><h5>This cannot be undone. All actions will be removed from '+opt.current_bot.name+' as well. ' +
              'Any previous interactions sent by '+opt.current_bot.name+' will be preserved.</h5></div></div>';
        },
        avatar_input : function () {
            return '<input style="display: none;" class="NS" id="bot_avatar_upload" type="file" name="profile_avatar_upload" accept="image/*">';
        }
    },
    handlers = {
        start : function(handler, edit){
            return '<div class="col-12 my-3 text-center h2"><span class="badge badge-light"><i class="fas fa-edit"></i> '+(edit ? 'Editing' : 'Creating')+' '+handler.name+':</span></div>' +
                '<div class="col-12 col-md-6 offset-md-3 text-center h3 mb-4"><i class="fas fa-info-circle"></i> '+handler.description+'</div> ' +
                '<div class="col-12 col-md-6 offset-md-3">' +
                '<form id="bot_action_form" action="">';
        },
        end : function(edit){
            let delete_btn = '';
            if(edit){
                delete_btn = '<button onclick="ThreadBots.removeAction(\''+opt.current_action.id+'\')" type="button" class="btn btn-lg btn-danger mr-3">Delete <i class="fas fa-trash-alt"></i></button>';
            }
            return '</form></div>' +
                '<hr><div class="col-12 text-center">' +
                '<button onclick="ThreadBots.reloadBotActions()" type="button" class="btn btn-lg btn-info mr-3">Cancel <i class="fas fa-undo"></i></button>' +
                delete_btn +
                '<button id="save_bot_action_btn" onclick="'+(edit ? 'ThreadBots.updateAction()' : 'ThreadBots.storeAction()')+'" type="button" class="btn btn-lg btn-success">'+(edit ? 'Update' : 'Save')+' <i class="fas fa-save"></i></button>' +
                '</div>';
        },
        base : function(values){
            let cooldown = 15, enabled = true, admin_only = false;
            if(values){
                cooldown = values.cooldown;
                enabled = values.enabled;
                admin_only = values.admin_only;
            }
            return '<div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '    <div class="col-12"><h5 class="font-weight-bold">Cooldown [in seconds]:</h5></div>' +
                '    <div class="input-group input-group-lg col-12 mb-0">\n' +
                '        <div class="input-group-prepend">\n' +
                '            <span class="input-group-text"><i class="fas fa-clock"></i></span>\n' +
                '         </div>\n' +
                '         <input type="number" autocomplete="off" min="0" max="900" class="form-control font-weight-bold shadow-sm" id="g_s_bot_cooldown" placeholder="Bot Cooldown" name="bot-cooldown-'+Date.now()+'" required value="'+cooldown+'">' +
                '     </div>\n' +
                '</div>'+
                '    <hr>\n' +
                '    <div class="form-row mx-n2 rounded bg-light text-dark pb-3 pt-2 px-3 shadow-sm">\n' +
                '        <label class="font-weight-bold h5 control-label" for="g_s_table">Action Toggles:</label>\n' +
                '        <table id="g_s_table" class="table mb-0 table-sm table-hover">\n' +
                '            <tbody>\n' +
                '            <tr class="'+(enabled ? 'alert-success' : '')+'">\n' +
                '                <td class="pointer_area" onclick="$(\'#g_s_action_enabled\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Enabled</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_action_enabled" name="g_s_action_enabled" type="checkbox" '+(enabled ? 'checked' : '')+'>\n' +
                '                        <label for="g_s_action_enabled"></label>\n' +
                '                    </span></div>\n' +
                '                </td>\n' +
                '            </tr>\n' +
                '            <tr class="'+(admin_only ? 'alert-success' : '')+'">' +
                '                <td class="pointer_area" onclick="$(\'#g_s_admin_only_action\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Admin Only</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_admin_only_action" name="g_s_admin_only_action" type="checkbox" '+(admin_only ? 'alert-success' : '')+'>\n' +
                '                        <label for="g_s_admin_only_action"></label>\n' +
                '                    </span></div>\n' +
                '                </td>\n' +
                '            </tr>\n' +
                '            </tbody>\n' +
                '        </table>\n' +
                '    </div>';
        },
        triggers : function(values, overrides){
            let triggers = '',
                readonly = false,
                text = '[Separate multiple triggers using commas ( , ) or the pipe ( | )]';
            if(overrides && overrides.length){
                triggers = overrides.join('|');
                readonly = true;
                text = '<span class="badge badge-warning">Cannot change</span>';
            } else if(values && values.length) {
                triggers = values.join('|');
            }
            return '<hr><div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '    <div class="col-12"><h5 class="font-weight-bold">Triggers: '+text+'</h5></div>' +
                '    <div class="input-group input-group-lg col-12 mb-0">\n' +
                '        <div class="input-group-prepend">\n' +
                '            <span class="input-group-text"><i class="fas fa-laptop-code"></i></span>\n' +
                '         </div>\n' +
                '         <input '+(readonly ? 'readonly' : '')+' autocomplete="off" class="form-control font-weight-bold shadow-sm" id="g_s_action_triggers" placeholder="!command | hello | sentence as a trigger" name="bot-triggers-'+Date.now()+'" required value="'+triggers+'">' +
                '     </div>\n' +
                '</div>';
        },
        match : function(value, override){
            let match = 'exact',
                readonly = false,
                text = '<span class="badge badge-warning">Cannot change</span>';
            if(override){
                match = override;
                readonly = true;
            } else if(value) {
                match = value;
            }
            return '<hr><div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '<div class="col-12"><h5 class="font-weight-bold">Match Method: '+(readonly ? text : '')+'</h5></div>' +
                '<div class="col-12 mb-3"><div class="col-12 col-lg-8 offset-lg-2">' +
                '<select id="g_s_action_match" class="custom-select custom-select-lg" '+(readonly ? 'disabled' : '')+'>\n' +
                '  <option value="contains" '+(match === "contains" ? 'selected' : '')+'>contains</option>\n' +
                '  <option value="contains:caseless" '+(match === "contains:caseless" ? 'selected' : '')+'>contains:caseless</option>\n' +
                '  <option value="contains:any" '+(match === "contains:any" ? 'selected' : '')+'>contains:any</option>\n' +
                '  <option value="contains:any:caseless" '+(match === "contains:any:caseless" ? 'selected' : '')+'>contains:any:caseless</option>\n' +
                '  <option value="exact" '+(match === "exact" ? 'selected' : '')+'>exact</option>\n' +
                '  <option value="exact:caseless" '+(match === "exact:caseless" ? 'selected' : '')+'>exact:caseless</option>\n' +
                '  <option value="starts:with" '+(match === "starts:with" ? 'selected' : '')+'>starts:with</option>\n' +
                '  <option value="starts:with:caseless" '+(match === "starts:with:caseless" ? 'selected' : '')+'>starts:with:caseless</option>\n' +
                '</select>' +
                '</div></div>'+
                '<div class="col-12 h6">' +
                '    <ul>' +
                '        <li>contains - The trigger can be anywhere within a message. Cannot be part of or inside another word.</li>' +
                '        <li>contains:caseless - Same as "contains", but is case insensitive.</li>' +
                '        <li>contains:any - The trigger can be anywhere within a message, including inside another word.</li>' +
                '        <li>contains:any:caseless - Same as "contains any", but is case insensitive.</li>' +
                '        <li>exact - The trigger must match the message exactly.</li>' +
                '        <li>exact:caseless - Same as "exact", but is case insensitive.</li>' +
                '        <li>starts:with - The trigger must be the lead phrase within the message. Cannot be part of or inside another word.</li>' +
                '        <li>starts:with:caseless - Same as "starts with", but is case insensitive.</li>' +
                '    </ul>' +
                '</div></div>';
        },
        reaction : function(reaction){
            let value = '';
            if(reaction){
                value = Messenger.format().shortcodeToUnicode(reaction);
            }
            return '<hr><div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '    <div class="col-12"><h5 class="font-weight-bold">Reaction: [emoji]</h5></div>' +
                '    <div class="input-group input-group-lg col-12 col-lg-6 offset-lg-3 mb-0">\n' +
                '         <input onclick="EmojiPicker.botActionReact()" readonly autocomplete="off" class="form-control font-weight-bold shadow-sm" id="g_s_bot_reaction" placeholder="Pick an emoji!" name="bot-reaction-'+Date.now()+'" required value="'+value+'">' +
                '         <div class="input-group-append">\n' +
                '           <button onclick="EmojiPicker.botActionReact()" class="btn btn-danger" type="button" id="bot_reaction_emoji_btn"><i class="fas fa-grin"></i></button>\n' +
                '         </div>' +
                '     </div>\n' +
                '</div>';
        },
        replies : function(payload){
            let quote = false, reply1 = '', reply2 = '', reply3 = '', reply4 = '', reply5 = '';
            if(payload){
                quote = payload.quote_original;
                reply1 = payload.replies[0] ? Messenger.format().shortcodeToUnicode(payload.replies[0]) : '';
                reply2 = payload.replies[1] ? Messenger.format().shortcodeToUnicode(payload.replies[1]) : '';
                reply3 = payload.replies[2] ? Messenger.format().shortcodeToUnicode(payload.replies[2]) : '';
                reply4 = payload.replies[3] ? Messenger.format().shortcodeToUnicode(payload.replies[3]) : '';
                reply5 = payload.replies[4] ? Messenger.format().shortcodeToUnicode(payload.replies[4]) : '';
            }
            return '<hr><div class="form-row mx-n2 rounded bg-light text-dark pb-3 pt-2 px-2 shadow-sm">\n' +
                '    <div class="col-12"><h5 class="font-weight-bold">Replies: [One required. Max of 5]</h5></div>' +
                '        <label class="control-label" for="g_replies_table"> - Quote matching message? When enabled, the first reply will use the matching message within the reply.</label>' +
                '        <table id="g_replies_table" class="table table-sm table-hover">\n' +
                '            <tbody>\n' +
                '            <tr class="'+(quote ? 'alert-success' : '')+'">\n' +
                '                <td class="pointer_area" onclick="$(\'#g_s_quote_original\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Quote matching message?</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_quote_original" name="g_s_quote_original" type="checkbox" '+(quote ? 'checked' : '')+'>\n' +
                '                        <label for="g_s_quote_original"></label>\n' +
                '                    </span></div>\n' +
                '                </td>\n' +
                '            </tr>\n' +
                '            </tbody>\n' +
                '        </table>\n' +
                '<input autocomplete="off" class="form-control form-control-lg font-weight-bold shadow-sm my-2" id="g_s_reply_1" placeholder="First reply..." name="bot-reply-'+Date.now()+'" value="'+reply1+'">' +
                '<input autocomplete="off" class="form-control form-control-lg font-weight-bold shadow-sm mb-2" id="g_s_reply_2" placeholder="Second reply..." name="bot-reply-'+Date.now()+'" value="'+reply2+'">' +
                '<input autocomplete="off" class="form-control form-control-lg font-weight-bold shadow-sm mb-2" id="g_s_reply_3" placeholder="Third reply..." name="bot-reply-'+Date.now()+'" value="'+reply3+'">' +
                '<input autocomplete="off" class="form-control form-control-lg font-weight-bold shadow-sm mb-2" id="g_s_reply_4" placeholder="Fourth reply..." name="bot-reply-'+Date.now()+'" value="'+reply4+'">' +
                '<input autocomplete="off" class="form-control form-control-lg font-weight-bold shadow-sm" id="g_s_reply_5" placeholder="Fifth reply..." name="bot-reply-'+Date.now()+'" value="'+reply5+'">' +
                '</div>';
        }
    };
    return {
        init : mounted.Initialize,
        viewBots : methods.viewBots,
        addBot : methods.addBot,
        viewBot : methods.viewBot,
        editBot : methods.editBot,
        removeAvatar : methods.removeAvatar,
        removeBot : methods.removeBot,
        viewAvailableHandlers : methods.viewAvailableHandlers,
        reloadBotActions : methods.reloadBotActions,
        createAction : methods.createAction,
        storeAction : methods.storeAction,
        editAction : methods.editAction,
        updateAction : methods.updateAction,
        removeAction : methods.removeAction,
        state : function(){
            return opt;
        },
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());