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
            let picker = new EmojiButton({
                theme: Messenger.common().dark_mode ? 'dark' : 'light',
            });
            picker.showPicker(message);
            picker.on('emoji', selection => {
                console.log(selection);
            });
            picker.on('hidden', () => {
                picker.destroyPicker()
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
        }
    };
    return {
        init : mounted.Initialize,
        addReaction : methods.addReaction,
        addMessage : methods.addMessage,
        lock : function(arg){
            if(typeof arg === 'boolean') opt.lock = arg
        }
    };
}());