$(function(){
    var plugin = {
        extensions:null,
        attach: function(fileList){
            var that = this;
            $.ajax({
                method: "GET",
                url: OC.generateUrl('/apps/wopi/getdiscovery'),
            }).done(function(data) {
                that.extensions = data.extensions + ',';
                if (that.extensions && that.extensions.length)
                    fileList.$fileList.on('click','td.filename>a.name', that.extensions, _.bind(that._onClickFile, fileList));
            });
        },
        _onClickFile: function(event){
            var $target = $(event.target);
            var ext = $target.closest('tr').find('.extension').text();
            if (ext && ext.length > 0 && event.data.indexOf(ext.substring(1) + ',') !== -1){
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