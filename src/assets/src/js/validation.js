//向yii.validation中注入一些校验的方法
yii.validation.dict = function(value, messages, options) {
    if (options.skipOnEmpty && yii.validation.isEmpty(value)) {
        return;
    }
    if(!options.multiple) {
        value = [value];
    }

    if(typeof value != "object") {
        yii.validation.addMessage(messages, options.message, value); 
        return;
    }
    
    if (options.min !== undefined && value.length < options.min) {
        yii.validation.addMessage(messages, options.tooSmall, value); 
    }
    if (options.max !== undefined && value.length > options.max) {
        yii.validation.addMessage(messages, options.tooMuch, value); 
    }
    
    for(let index in value) {
        let val = value[index];
        if( (!options.list.hasOwnProperty(val)) || options.excludes.hasOwnProperty(val)) {
            yii.validation.addMessage(messages, options.message, value);
        }
    }
}
