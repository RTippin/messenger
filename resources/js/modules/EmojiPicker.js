import { EmojiButton } from '@joeattardi/emoji-button';

window.EmojiPicker = (function () {
    var opt = {
        lock : true,
    },
    mounted = {
        Initialize : function () {
            opt.lock = false;
        },
    },
    methods = {
        addReaction : function(messageId){
            let message = document.getElementById('message_'+messageId).getElementsByClassName('message-body')[0];
            message.classList.add('shadow-primary');
            let picker = new EmojiButton({
                theme: Messenger.common().dark_mode ? 'dark' : 'light',
            });
            picker.showPicker(message);
            picker.on('emoji', selection => {
                ThreadManager.addNewReaction({
                    message_id : messageId,
                    emoji : selection.emoji
                });
            });
            picker.on('hidden', () => {
                picker.destroyPicker();
                message.classList.remove('shadow-primary');
            });
        },
        addMessage : function(){
            let input = document.getElementById('message_text_input');
            let picker = new EmojiButton({
                theme: Messenger.common().dark_mode ? 'dark' : 'light',
                autoHide : false,
                position: 'top-end'
            });
            picker.showPicker(input);
            picker.on('emoji', selection => {
                let curPos = input.selectionStart;
                let curVal = input.value;
                input.value = curVal.slice(0,curPos)+selection.emoji+curVal.slice(curPos)
            });
            picker.on('hidden', () => {
                picker.destroyPicker()
            });
        },
        editMessage : function(){
            let input = document.getElementById('edit_message_textarea');
            let btn = document.getElementById('edit_message_emoji_btn');
            let picker = new EmojiButton({
                theme: Messenger.common().dark_mode ? 'dark' : 'light',
                autoHide : false,
                position: 'bottom-end'
            });
            picker.showPicker(btn);
            picker.on('emoji', selection => {
                let curPos = input.selectionStart;
                let curVal = input.value;
                input.value = curVal.slice(0,curPos)+selection.emoji+curVal.slice(curPos)
            });
            picker.on('hidden', () => {
                picker.destroyPicker()
            });
        }
    };
    return {
        init : mounted.Initialize,
        addReaction : methods.addReaction,
        addMessage : methods.addMessage,
        editMessage : methods.editMessage,
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());