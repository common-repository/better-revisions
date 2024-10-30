( function( $, window ) {


    if(window.autosave = true){

        function splitPostDate( post_date ) {
            var dArray = post_date.split( 'T' );
            var dateArray = dArray[0].split( '-' );
            var timeArray = dArray[1].split( ':' );
            var postDateArray = {
                'year' : dateArray[0],
                'month' : dateArray[1],
                'day' : dateArray[2],
                'hour' : timeArray[0],
                'minute' : timeArray[1],
                'second' : timeArray[2]
            };
            return postDateArray;
        }

        $(document).ready(function(){
            // we clean the local aplication storage, because in multiuser environment it's only confusing
            var blog_id = typeof window.autosaveL10n !== 'undefined' && window.autosaveL10n.blog_id;
            var key     = 'wp-autosave-'+blog_id;
            sessionStorage.removeItem(key);
        });

        $(document).on('heartbeat-tick.autosave', function( event, data ) {
            if ( data.server_time ) {

                if (revisions_js_object.gutenbergIsUsed) {
                    var editor_data = wp.data.select('core/editor');

                    var post_author = editor_data.getEditedPostAttribute( 'author' );
                    var post_date = splitPostDate( editor_data.getEditedPostAttribute( 'date' ) );
                    var post_status = editor_data.getEditedPostAttribute( 'status' );
                    var comment_status = editor_data.getEditedPostAttribute( 'comment_status' );
                    var ping_status = editor_data.getEditedPostAttribute( 'ping_status' );
                    var post_password = editor_data.getEditedPostAttribute( 'password' );
                    var visibility = editor_data.getEditedPostVisibility();
                    var post_name = editor_data.getEditedPostAttribute( 'slug' );
                    var post_parent = editor_data.getEditedPostAttribute( 'parent' );
                    var menu_order = editor_data.getEditedPostAttribute( 'menu_order' );
                } else {

                    var post_author =  $('#post_author_override').val();
                    var post_date = {'year': $('#aa').val(),'month': $('#mm').val(),'day': $('#jj').val(),'hour': $('#hh').val(),'minute': $('#mn').val(),'second': $('#ss').val()};
                    var post_status = $('#post_status').val();
                    var comment_status = $('#comment_status:checked').val();
                    var ping_status = $('#ping_status:checked').val();
                    var post_password = $('#post_password').val();
                    var visibility = $('input[name=visibility]:checked').val();
                    var post_name = $('#editable-post-name-full').text();
                    var post_parent = $('#parent_id').val();
                    var menu_order = $('#menu_order').val();
                }

                var newData = {
                    'post_author' : post_author,
                    'post_date' : post_date,
                    'post_status' : post_status,
                    'comment_status' : comment_status,
                    'ping_status' : ping_status,
                    'post_password' : post_password,
                    'visibility' : visibility,
                    'post_name' : post_name,
                    'post_parent' : post_parent,
                    'menu_order' : menu_order
                };

                var meta = {
                    'action': revisions_js_object.ajaxCall,
                    'nonce': revisions_js_object.nonce,
                    'postID': revisions_js_object.postID,
                    'userID': revisions_js_object.userID,
                    'meta': newData
                };
                jQuery.post(ajaxurl, meta, function(response) {
                    if (response == 'success'){
                        console.log('autosave ok');
                    } else {
                        console.log('autosave error');
                    }
                });
            }
        });
    }
}( jQuery, window ));
