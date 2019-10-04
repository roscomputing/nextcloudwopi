$(function(){
    var plugin = {
        attach: function(fileList){
            fileList.$fileList.on('click','td.filename>a.name', _.bind(this._onClickFile, fileList));
        },
        _onClickFile: function(event){
            var $target = $(event.target);
            if ($target.closest('tr').find('.extension').text() == ".docx"){
                event.preventDefault();
                var newForm = jQuery('<form>', {
                    'action': OC.generateUrl('/apps/wopi/editor'),
                    'target': '_blank',
                    'method': 'post'
                }).append(jQuery('<input>', {
                    'name': 'requesttoken',
                    'value': OC.requestToken,
                    'type': 'hidden'
                })).append(jQuery('<input>', {
                    'name': 'id',
                    'value': $target.closest("tr").attr("data-id"),
                    'type': 'hidden'
                })).hide().appendTo('body');
                newForm.submit();
            }
        }
    };
    OC.Plugins.register('OCA.Files.FileList', plugin);
});