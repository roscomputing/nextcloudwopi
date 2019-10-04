$(function(){
    var $frame = $("<iframe/>",{
        'name':'office_frame',
        'id':'office_frame',
        // The title should be set for accessibility
        'title':'Office Frame',
        // This attribute allows true fullscreen mode in slideshow view
        // when using PowerPoint's 'view' action.
        'allowfullscreen':'true',
        // The sandbox attribute is needed to allow automatic redirection to the O365 sign-in page in the business user flow,
        'sandbox':'allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation allow-popups-to-escape-sandbox'
    }).appendTo('body');
    $("#office_form").submit();
});