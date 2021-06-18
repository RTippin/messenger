window.ThreadBots = (function () {
    var opt = {
        lock : true,
        thread : null,
        bots_table : null,
        current_bot : null,
        avatar_input : null,
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
                    LazyImages.update();
                }
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
                        if (bot.hasOwnProperty('actions')) {
                            methods.loadDataTable($("#view_bots_actions_table"), 'actions')
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
                size : 'md',
                onReady : gather,
                cb_btn_txt : 'Save Bot',
                cb_btn_icon : 'robot',
                cb_btn_theme : 'success',
                callback : function(){
                    methods.updateBot(id)
                }
            });
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
                        '<a class="dropdown-item" onclick="ThreadBots.removeBot(\''+bot.id+'\'); return false;" href="#" title="Remove"><i class="fas fa-trash-alt"></i> Remove</a>';
                return '<tr id="row_'+bot.id+'">\n' +
                    '     <td class="pointer_area" onclick="ThreadBots.viewBot(\''+bot.id+'\')">\n' +
                    '      <div class="table_links">\n' +
                    '        <div class="nowrap">\n' +
                    '          <img alt="Bot Avatar" class="lazy rounded-circle group-image avatar-is-'+online+'" data-src="'+bot.avatar.sm+'" />\n' +
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
                    '       <a class="dropdown-item" onclick="ThreadBots.viewBot(\''+bot.id+'\'); return false;" href="#" title="View"><i class="fas fa-robot"></i> '+(opt.thread.options.manage_bots ? 'Manage' : 'View')+'</a>' +
                            (opt.thread.options.manage_bots ? manage : '') +
                    ' </span>\n' +
                    ' </div></div></td>\n' +
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
            return '<form id="new_bot_form" action="">\n' +
                '<div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '    <h5>Bot Name:</h5>' +
                '    <div class="input-group input-group-lg col-12 mb-0">\n' +
                '        <div class="input-group-prepend">\n' +
                '            <span class="input-group-text"><i class="fas fa-robot"></i></span>\n' +
                '         </div>\n' +
                '         <input autocomplete="off" minlength="2" class="form-control font-weight-bold shadow-sm" id="g_s_bot_name" placeholder="Bot Name" name="bot-name-'+Date.now()+'" required value="'+bot.name+'">' +
                '     </div>\n' +
                '</div>' +
                '<hr>' +
                '<div class="form-row mx-n2 rounded bg-light text-dark pt-2 pb-3 px-2 shadow-sm">\n' +
                '    <h5>Cooldown (in seconds):</h5>' +
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
                '    <div class="text-center form-group mb-0 py-2 alert-danger shadow rounded">\n' +
                '        <div class="mb-1 font-weight-bold">You will be asked to confirm this action</div>\n' +
                '        <button onclick="ThreadBots.removeBot(\''+bot.id+'\')" type="button" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Remove Bot</button>\n' +
                '    </div>\n' +
                '</form>';
        },
        view_bot : function(bot){
            let online = bot.enabled ? (bot.on_cooldown ? 'away' : 'online') : 'offline',
                actions = '<div class="col-12 mt-5 text-center h2"><span class="badge badge-light"><i class="fas fa-eye-slash"></i> Actions are hidden</span></div>',
                editable = '<hr class="mt-2"><div class="row"><div class="col-12">' +
                    '<button onclick="ThreadBots.editBot(\''+bot.id+'\')" type="button" class="btn btn-sm btn-primary mr-3 mb-2">Edit <i class="fas fa-edit"></i></button>' +
                    '<button onclick="" type="button" class="btn btn-sm btn-primary mr-3 mb-2">Add Actions <i class="fas fa-server"></i></button>' +
                    '<button class="btn btn-sm btn-success dropdown-toggle mr-3 mb-2" type="button" id="botAvatarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\n' +
                    '  Avatar <i class="fas fa-image"></i>' +
                    '</button>\n' +
                    '  <div class="dropdown-menu" aria-labelledby="botAvatarDropdown">\n' +
                    '    <a onclick="$(\'#bot_avatar_upload\').click(); return false;" class="dropdown-item" href="#"><i class="fas fa-image"></i> Upload Avatar</a>\n' +
                    '    <a onclick="ThreadBots.removeAvatar(); return false;" class="dropdown-item" href="#"><i class="fas fa-trash"></i> Remove Avatar</a>\n' +
                    '  </div>\n' +
                    '<button onclick="ThreadBots.removeBot(\''+bot.id+'\')" type="button" class="btn btn-sm btn-danger mr-3 mb-2">Remove <i class="fas fa-trash-alt"></i></button>' +
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
                '                    <th>Description</th>\n' +
                '                    <th>Enabled</th>\n' +
                '                    <th>On Cooldown</th>\n' +
                '                    <th>Cooldown</th>\n' +
                '                    <th>Admin Only</th>\n' +
                '                    <th>Triggers</th>\n' +
                '                    <th>Match</th>\n' +
                '                    <th>Payload</th>\n' +
                '                </tr>\n' +
                '                </thead>\n' +
                '                <tbody>',
                table_bot = '</tbody></table></div></div></div>',
                table_fill = '';
            let action_fill = (action) => {
                let triggers = '';
                action.triggers.forEach((trigger) => triggers += '<span class="badge badge-light mr-1">'+trigger+'</span>');
                return '<tr id="row_'+action.id+'">\n' +
                    '  <td class="h5 nowrap">'+action.handler.name+'</td>'+
                    '  <td class="h5 nowrap">'+action.handler.description+'</td>'+
                    '  <td class="h5">\n' +
                    (action.enabled ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times"></i></span>') +
                    '  </td>\n' +
                    '  <td class="h5">\n' +
                    (action.on_cooldown ? '<span class="badge badge-danger"><i class="fas fa-hourglass-half"></i> Yes</span>' : '<span class="badge badge-success"><i class="fas fa-check"></i> No</span>') +
                    '  </td>\n' +
                    '  <td class="h5"><span class="badge badge-primary">'+action.cooldown+' seconds</span></td>\n' +
                    '  <td class="h5">\n' +
                    (action.admin_only ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times"></i></span>') +
                    '  </td>\n' +
                    '  <td class="h5">'+triggers+'</td>'+
                    '  <td class="h5"><span class="badge badge-primary">'+action.match+'</span></td>'+
                    '  <td class="h5 nowrap action-payload">'+templates.handler_payload(action)+'</td>'+
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
                    action.payload.replies.forEach((reply) => replies += '<li>'+reply+'</li>');
                    return replies + '</ul>';
            }
            return 'N/A';
        },
        warn_delete : function(){
          return 'Really Delete <strong>'+opt.current_bot.name+'</strong>?'+
              '<div class="card mt-3"><div class="card-body bg-warning shadow rounded"><h5>This cannot be undone. All actions will be removed from '+opt.current_bot.name+' as well. ' +
              'Any previous interactions sent by '+opt.current_bot.name+' will be preserved.</h5></div></div>';
        },
        avatar_input : function () {
            return '<input style="display: none;" class="NS" id="bot_avatar_upload" type="file" name="profile_avatar_upload" accept="image/*">';
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
        state : function(){
            return opt;
        },
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());