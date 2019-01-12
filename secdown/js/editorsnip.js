tinymce.PluginManager.add('slink_button_plugin', function(editor, url) {
    // Add a button that opens a window
    aurl = url.replace('/js', '');
    editor.addButton('slink', {
        text: '',
        title : "Insert Secure Link",
        icon: true,
        image : aurl+"/image.png",
        onclick: function() {
            // Open window
            editor.windowManager.open({
                title: 'Insert Secure Link',
                width: 640,
                height: 515,
                body: [
                    {type: 'textbox', name: 'url', label: 'URL (Downloadable File)' },
                    //{type: 'container',html: 'Enter URL to the Downloadable File'},
                    {type: 'textbox', name: 'name', label: 'Fake File Name (optional)'},

      
                    {type: 'textbox', name: 'roles', label: 'User roles (optional) ex: author,subscriber'},
                    {type: 'textbox', name: 'users', label: 'Usernames (Users who can view link) optional'},
                    {type: 'textbox', name: 'message', label: 'Message for other users (optional)'}, 
                      {type: 'textbox', name: 'extension', label: 'File Extension (optional) ex: jpg, png'},                					{type: 'radio', name: 'notdownload', value:"1", label: 'Not A Download (Webpage) optional'},                  
 
                    {type: 'textbox', name: 'id', label: 'CSS id (for style/ custom JS)optional'},                    
                    {type: 'textbox', name: 'aclass', label: 'CSS class (optional) style ex: button'},
                    {type: 'textbox', name: 'style', label: 'CSS style (optional) ex: color:red;'},                    
                    {type: 'textbox', name: 'title',  label: 'Title (The HTML title attribute) optional'}, 
                    {type: 'textbox', name: 'onclick', label: 'Onclick (custom JS) optional'},		               
                   {type: 'radio', name: 'onlylink',value:"1", label: 'Only Link (optional)'}, 					     
                ],
                onsubmit: function(e) {
                    // Insert content when the window form is submitted
                    eparam = '';
                    if(e.data.title!=''){
                    eparam = eparam+' url="'+e.data.url+'"';	
                    }
                    if(e.data.name!=''){
                    eparam = eparam+' name="'+e.data.name+'"';	
                    }

                    if(e.data.roles!=''){
                    eparam = eparam+' roles="'+e.data.roles+'"';	
                    } 
                    if(e.data.users!=''){
                    eparam = eparam+' users="'+e.data.users+'"';	
                    }
                    if(e.data.message!=''){
                    eparam = eparam+' message="'+e.data.message+'"';	
                    } 
                    if(e.data.extension!=''){
                    eparam = eparam+' extension="'+e.data.extension+'"';	
                    }	
                    if(e.data.notdownload!=''){
                    eparam = eparam+' notdownload="'+e.data.notdownload+'"';	
                    } 
                      if(e.data.id!=''){
                    eparam = eparam+' id="'+e.data.id+'"';	
                    }
                    if(e.data.aclass!=''){
                    eparam = eparam+' class="'+e.data.aclass+'"';	
                    } 
                    if(e.data.style!=''){
                    eparam = eparam+' style="'+e.data.style+'"';	
                    }                     
                    if(e.data.title!=''){
                    eparam = eparam+' title="'+e.data.title+'"';	
                    }
                    if(e.data.onclick!=''){
                    eparam = eparam+' onclick="'+e.data.onclick+'"';	
                    }                     
                    if(e.data.onlylink!=''){
                    eparam = eparam+' onlylink="'+e.data.onlylink+'"';	
                    }																							                 				selected =editor.selection.getContent({format : 'html'});
                    editor.insertContent('[slink url="' + e.data.url+'" '+eparam+']'+selected+'[/slink]');
                }
            });
        }
    });

    // Adds a menu item to the tools menu
    editor.addMenuItem('slink', {
        text: 'Insert Secure Link',
        context: 'tools',
        onclick: function() {
            // Open window with a specific url
            editor.windowManager.open({
                title: 'WP Secure Links',
                url: 'http://wpsecurelinks.sixthlife.net',
                width: 800,
                height: 515,
                buttons: [{
                    text: 'Close',
                    onclick: 'close'
                }]
            });
        }
    });
});
tinymce.PluginManager.add('sslink_button_plugin', function(editor, url) {
    // Add a button that opens a window
    aurl = url.replace('/js', '');
    editor.addButton('sslink', {
        text: '',
        title : "Insert Secure Subscribe",
        icon: true,
        image : aurl+"/secure.png",
        onclick: function() {
            // Open window
            editor.windowManager.open({
                title: 'Insert Secure Subscribe',
                width: 640,
                height: 450,
                body: [
                    {type: 'textbox', name: 'url', label: 'URL (Downloadable File)' },
                    //{type: 'container',html: 'Enter URL to the Downloadable File'},
                    {type: 'textbox', name: 'name', label: 'Fake File Name (optional)'},
      				{type: 'radio', name: 'sdownload' ,value:"1", label: 'Show Subscribe Form '},                              {type: 'textbox', name: 'slist' , label: 'Mailpoet List Name (optional) '},   
          {type: 'textbox', name: 'extension', label: 'File Extension (optional) ex: jpg, png'},                
          {type: 'radio', name: 'notdownload', value:"1", label: 'Not A Download (Webpage) optional'},      
                     {type: 'textbox', name: 'id', label: 'CSS id (for style/ custom JS) optional'},                    
                    {type: 'textbox', name: 'aclass', label: 'CSS class (for style ex: button) optional'},
                    {type: 'textbox', name: 'style', label: 'CSS style (optional) ex: color:red;'},                    
                    {type: 'textbox', name: 'title',  label: 'Title (The HTML title attribute) optional'}, 
                    {type: 'textbox', name: 'onclick', label: 'Onclick (custom JS) optional'},

 		     
                ],
                onsubmit: function(e) {
                    // Insert content when the window form is submitted
                    eparam = '';
                    if(e.data.title!=''){
                    eparam = eparam+' url="'+e.data.url+'"';	
                    }
                    if(e.data.name!=''){
                    eparam = eparam+' name="'+e.data.name+'"';	
                    }
                      if(e.data.id!=''){
                    eparam = eparam+' id="'+e.data.id+'"';	
                    }
                    if(e.data.aclass!=''){
                    eparam = eparam+' class="'+e.data.aclass+'"';	
                    } 
                    if(e.data.style!=''){
                    eparam = eparam+' style="'+e.data.style+'"';	
                    }                     
                    if(e.data.title!=''){
                    eparam = eparam+' title="'+e.data.title+'"';	
                    }
                    if(e.data.onclick!=''){
                    eparam = eparam+' onclick="'+e.data.onclick+'"';	
                    } 
                    if(e.data.extension!=''){
                    eparam = eparam+' extension="'+e.data.extension+'"';	
                    }	
                    if(e.data.notdownload!=''){
                    eparam = eparam+' notdownload="'+e.data.notdownload+'"';	
                    } 
                    if(e.data.sdownload!=''){
                    eparam = eparam+' sdownload="'+e.data.sdownload+'"';	
                    }
                    if(e.data.slist!=''){
                    eparam = eparam+' slist="'+e.data.slist+'"';	
                    } 
																						                 				selected =editor.selection.getContent({format : 'html'});
                    editor.insertContent('[slink url="' + e.data.url+'" '+eparam+']'+selected+'[/slink]');
                }
            });
        }
    });

    // Adds a menu item to the tools menu
    editor.addMenuItem('sslink', {
        text: 'Insert Secure Subscribe',
        context: 'tools',
        onclick: function() {
            // Open window with a specific url
            editor.windowManager.open({
                title: 'WP Secure Links',
                url: 'http://wpsecurelinks.sixthlife.net',
                width: 800,
                height: 450,
                buttons: [{
                    text: 'Close',
                    onclick: 'close'
                }]
            });
        }
    });
});