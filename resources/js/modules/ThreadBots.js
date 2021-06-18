window.ThreadBots = (function () {
    var opt = {
        lock : true,
        thread : null,
        bots_table : null,
    },
    mounted = {
        Initialize : function () {
            opt.lock = false;
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
                    success : function(data){
                        Messenger.alert().fillModal({
                            body : templates.view_bots(data),
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
                    success : function(data){
                        Messenger.alert().fillModal({
                            body : templates.view_bot(data),
                            title : data.name
                        });
                        if (data.hasOwnProperty('actions')) {
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
                Messenger.xhr().request({
                    route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots/'+id,
                    success : function(data){
                        Messenger.alert().fillModal({
                            body : templates.edit_bot(data),
                            title : 'Editing '+data.name
                        });
                        $(".m_setting_toggle").change(function(){
                            $(this).is(':checked') ? $(this).closest('tr').addClass('alert-success') : $(this).closest('tr').removeClass('alert-success')
                        })
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
                size : 'md',
                onReady : gather
            });
        },
        storeBot : function(){
            if (!methods.setThread()) return;
            let cooldown =  $("#g_s_bot_cooldown");
            Messenger.xhr().payload({
                route : Messenger.common().API + 'threads/' + opt.thread.id + '/bots',
                data : {
                    name : $('#g_s_bot_name').val(),
                    enabled : $("#g_s_bot_enabled").is(":checked"),
                    hide_actions : $("#g_s_hide_actions").is(":checked"),
                    cooldown : cooldown.val().length ? cooldown.val() : 0,
                },
                success : methods.viewBots,
                fail_alert : true,
            });
        }
    },
    templates = {
        view_bots : function(bots){
            let add_btn = '<div class="col-12 text-center mt-3"><button onclick="ThreadBots.addBot()" type="button" class="btn btn-success">Add Bot <i class="fas fa-robot"></i></button></div>';
            return templates.bots(bots) + (opt.thread.options.manage_bots ? add_btn : '');
        },
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
                let online = bot.enabled ? (bot.on_cooldown ? 'away' : 'online') : 'offline';
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
                    '       <a class="dropdown-item" onclick="ThreadBots.viewBot(\''+bot.id+'\'); return false;" href="#" title="View"><i class="fas fa-robot"></i> View</a>' +
                    '       <a class="dropdown-item" onclick="ThreadBots.editBot(\''+bot.id+'\'); return false;" href="#" title="View"><i class="fas fa-edit"></i> Edit</a>' +
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
                '            <tr class="'+(bot.admin_only ? 'alert-success' : '')+'">\n' +
                '                <td class="pointer_area" onclick="$(\'#g_s_hide_actions\').click()">\n' +
                '                    <div class="h4 mt-1"><i class="fas fa-caret-right"></i> <span class="h5">Hide Actions</span></div>\n' +
                '                </td>\n' +
                '                <td>\n' +
                '                    <div class="mt-1 float-right"><span class="switch switch-sm mt-1">\n' +
                '                        <input class="switch switch_input m_setting_toggle" id="g_s_hide_actions" name="g_s_hide_actions" type="checkbox" '+(bot.admin_only ? 'checked' : '')+'>\n' +
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
                '        <button onclick="" type="button" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Remove Bot</button>\n' +
                '    </div>\n' +
                '</form>';
        },
        view_bot : function(bot){
            let online = bot.enabled ? (bot.on_cooldown ? 'away' : 'online') : 'offline',
                actions = '',
                editable = '<hr class="mt-2"><div class="row"><div class="col-12 text-center">' +
                    '<button onclick="" type="button" class="btn btn-primary mr-3">Add Actions <i class="fas fa-robot"></i></button>' +
                    '<button onclick="ThreadBots.editBot(\''+bot.id+'\')" type="button" class="btn btn-primary">Edit Bot <i class="fas fa-edit"></i></button>' +
                    '</div></div><hr>';
            if (bot.hasOwnProperty('actions')) {
                actions = templates.bot_actions_table(bot.actions);
            }
            return '<div class="row"><div class="col-12 col-md-6 mb-3">' +
                '<img alt="Avatar" height="75" width="75" class="float-left mr-3 rounded avatar-is-'+online+'" src="'+bot.avatar.md+'"/>\n' +
                '<h3 class="font-weight-bold">'+bot.name+'</h3>' +
                '<h5>Creator: '+bot.owner.name+'</h4>' +
                '</div>' +
                '<div class="col-12 col-md-6 h5">' +
                '<div class="float-right">' +
                '<div class="col-12 mb-2">Enabled : ' + (bot.enabled ? '<span class="badge badge-success"><i class="fas fa-check"></i></span>' : '<span class="badge badge-danger"><i class="fas fa-times"></i></span>') + '</div>' +
                '<div class="col-12 mb-2">Cooldown : <span class="badge badge-primary mr-3">'+bot.cooldown+' seconds</span></div>' +
                '<div class="col-12">On cooldown? ' + (bot.on_cooldown ? '<span class="badge badge-danger"><i class="fas fa-hourglass-half"></i> Yes</span>' : '<span class="badge badge-success"><i class="fas fa-check"></i> No</span>') + '</div>' +
                '</div></div></div>' +
                (opt.thread.options.manage_bots ? editable : '') + actions;
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
        }
    };
    return {
        init : mounted.Initialize,
        viewBots : methods.viewBots,
        addBot : methods.addBot,
        viewBot : methods.viewBot,
        editBot : methods.editBot,
        state : function(){
            return opt;
        },
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());