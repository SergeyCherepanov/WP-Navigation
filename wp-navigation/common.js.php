<?php
    /**
     * Navigation Manager - Wordpress Plugin.
     *
     * @copyright:    Copyright 2008 Sergey Cherepanov. (http://www.cherepanov.org.ua)
     * @author:       Sergey Cherepanov (sergey@cherepanov.org.ua)
     * @license:      http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE v3.0
     * @date          16.12.08
     */
    header("Content-type: text/javascript; charset=utf-8");
    include_once('../../../wp-config.php');
?>
Function.prototype.bind=function(object){
    var method = this;
    return function() {return method.apply(object, arguments);}
}
// ControlNavList Class
function ControlNavList(params){
    this.__construnt = function(params){
        
        this.currentGroup = <?php echo ( empty($navigation->groups) ? 'null' : current($navigation->groups)->ID); ?>;
        
        return true;
    }
    
    this.remove = function(id){
        _this = this;
        if(!id){
            return false;
        }
        jQuery(
            function($){
                
                $.post(
                    '<?php echo admin_url('index.php');?>',
                    {
                        nav_request:1,
                        action:'delete_item',
                        item_id:id,
                        group_id:_this.currentGroup
                    },
                    function(result){
                        eval('var result = '+result);
                        
                        var nav_item_parent = $('#navigation-item-'+id).get(0).parentNode.parentNode;
                        
                        $('#navigation-item-'+id).remove();
                        
                        if(!$('#'+nav_item_parent.id+' > ul > li').get(0)){
                            
                            $('#'+nav_item_parent.id+' > ul').remove();
                            $('#'+nav_item_parent.id+' > span.handler').remove();
                            
                        }
                        
                        
                        var parents_select = $('#edititem #itemparent');

                        parents_select.empty();
                        parents_select.append(result.parents);
                        
                        endloading();
                    }
                );
                loading();
            }
        );
        navEditForm.newItem();
    }
    this.select = function(id){
        
        var _this = this;
        jQuery(
                function($){
                    
                    $.post(
                        '<?php echo admin_url('index.php');?>',
                        {
                            nav_request:1,
                            action:'item_to_edit',
                            item_id:id
                        },
                        function(result){
                            
                            eval('var result = '+result);
                            
                            navEditForm.reset();
                            
                            $('#edititem input[type=radio]').attr('checked', '');
                            $('#edititem input[type=checkbox]').attr('checked', '');
                            
                            
                            $('#edititem li.'+result.type+'-type input[type=radio]').attr('checked', 'cheched');
                            
                            var input;
                            
                            if(input = $('#edititem li.'+result.type+'-type select').get(0)){
                                $('#edititem li.'+result.type+'-type select option[value='+result.value+']').attr('selected', 'selected');
                            }else if(input = $('#edititem li.'+result.type+'-type input[type=text]').get(0)){
                                input.value = result.value;
                            }
                            if(parseInt(result.custom_title)){
                                $('#edititem .custom-title input[type=text]').attr('value', result.name);
                            }
                            
                            if(typeof(result['extends'].css) != 'undefined'){
                                $('#edititem .css-class input[type=text]').attr('value', result['extends'].css);
                            }
                            if(typeof(result['extends'].link_title) != 'undefined'){
                                $('#edititem .link-title input[type=text]').attr('value', result['extends'].link_title);
                            }
                            if(parseInt(result.parent)){
                                $('#edititem .parent select option[value='+result.parent+']').attr('selected', 'selected');
                            }
                            
                            if(parseInt(result.show_child)){
                                $('#edititem .show-child input').attr('checked', 'checked');
                            }
                            
                            $('.form-title').text("<?php _e('Edit item');?>");
                            
                            $('#edititem input[name=action]').attr('value', 'edit_item');
                            $('#edititem input[name=ID]').attr('value', result.ID);
                            
                            
                            $('#edititem p.edit').css('display', 'block');
                            $('#edititem p.add').css('display', 'none');
                            
                            
                            $('#edititem .parent select option[class*=-'+id+'-]').attr('disabled', 'disabled');
                            
                            $('#edititem .parent select option[value='+id+']').attr('disabled', 'disabled');
                            
                            
                            endloading();
                        }
                    );
                    loading();
                    
                    $('#navigation-items li').removeClass("active");
                    $('#navigation-item-'+id).addClass("active");
                    
                    
                    
                }
        );
        return false;
    }
    this.__construnt(params);
}
// End ControlNavList Class

// ControlNavForm Class
function ControlNavForm(params) {
    this.__construct = function(params){
        return true;
    }
    
    this.reset = function(){
        var _this = this;
        jQuery(
            function($){
                $('#edititem input[type=text]').attr('value', '');
                $('#edititem select option').attr('selected', false);
                $('#edititem select option').attr('disabled', false);
                $('#edititem select option[value=]').attr('selected', 'selected');
                $('#edititem #itemgroup').attr('value', navItemList.currentGroup);
            }
        );
    }
    this.newItem = function(){
        var _this = this;
        jQuery(
            function($){
                _this.reset();
                
                $('#edititem input[name=action]').attr('value', 'add_item');
                $('#edititem input[name=ID]').attr('value', '');
                $('#edititem p.edit').css('display', 'none');
                $('#edititem p.add').css('display', 'block');
                
                $('.form-title').text("<?php _e('Add new item');?>");
                
                
                if(paretn_item = $('#navigation-items li.active').get(0)){
                    var parent_id  = paretn_item.id.replace('navigation-item-', '');
                }
                if(parent_id){
                    $('#edititem select#itemparent option[value='+parent_id+']').attr('selected', 'selected');
                }
            }
        );
    }
    this.__construct(params);
}
// End ControlNavForm Class


var navItemList = new ControlNavList();
var navEditForm = new ControlNavForm();

//navItemList.currentGroup = null;

jQuery(
        function($){
            $(document).ready(
                function(){
                        $('#edititem .item-types input[type=radio]').click(function(){
                            if(this.value == 'category' || this.value == 'page'){
                                $('#edititem .show-child').css({visibility:'visible'}).animate({opacity:1}, 500);
                            }else{
                                $('#edititem .show-child').animate({opacity:0}, 500, '', function(){$(this).css({visibility:'hidden'});$('#edititem .show-child input').attr('checked', '')});
                            }
                        })
                        
                        
                        $('ul.tabs a').click(
                            function(){
                                $('td.group_options div.section').removeClass('act');
                                $($(this).attr('href')).addClass('act');
                                return false;
                            }
                        );
                        
                        $('input[name=usejs], select[name=usejs_method]').change(
                            function(){
                                $.post(
                                    '<?php echo admin_url('index.php');?>',
                                    {
                                        nav_request:1,
                                        action:'usejs',
                                        group_ID:navItemList.currentGroup,
                                        value:$('input[name=usejs]').attr('checked'),
                                        method:$('select[name=usejs_method]').attr('value')
                                    },
                                    function(){
                                        endloading();
                                    }
                                );
                                loading();
                            }
                        );
                        
                        $('#edititem p.edit .delete').click(function(){
                                if(confirm("<?php _e('Delete item?');?>")){
                                        navItemList.remove($('#edititem #ID').attr('value'));
                                }return false;
                            }
                        );
                        
                        
                        $('#edititem p.edit .up').click(function(){
                            var ID = $('#edititem #ID').attr('value');
                            $.post(
                                '<?php echo admin_url('index.php');?>',
                                {
                                    nav_request:1,
                                    action:'item_up',
                                    item_id:ID
                                },
                                function(result){
                                    if(result){
                                        eval('var result = '+result+';');
                                        
                                        var element = $('#navigation-items #navigation-item-'+ID).get(0);
                                        var parents_select = $('#edititem #itemparent');
                                        var parentid = $('#edititem #itemparent').attr('value');
                                        
                                        upelement(element);
                                        
                                        
                                        
                                        parents_select.empty();
                                        parents_select.append(result);
                                        
                                        $('#edititem #itemparent option[value='+parentid+']').attr('selected', 'selected');
                                        
                                        $('#edititem .parent select option[class*=-'+ID+'-]').attr('disabled', 'disabled');
                                        $('#edititem .parent select option[value='+ID+']').attr('disabled', 'disabled');
                                    }
                                    endloading();
                                }
                            );
                            loading();
                            return false;
                        });
                        
                        $('button.addNewItem').click(function(){navEditForm.newItem();$('button.addNewItem').css('visibility', 'hidden')})
                        
                        $('#edititem p.edit .down').click(function(){
                            var ID = $('#edititem #ID').attr('value');
                            $.post(
                                '<?php echo admin_url('index.php');?>',
                                {
                                    nav_request:1,
                                    action:'item_down',
                                    item_id:ID
                                },
                                function(result){
                                    if(result){
                                        eval('var result = '+result+';');
                                        
                                        var element = $('#navigation-items #navigation-item-'+ID).get(0);
                                        var parents_select = $('#edititem #itemparent');
                                        var parentid = $('#edititem #itemparent').attr('value');

                                        downelement(element);
                                        
                                        
                                        
                                        parents_select.empty();
                                        parents_select.append(result);
                                        
                                        $('#edititem #itemparent option[value='+parentid+']').attr('selected', 'selected');
                                        
                                        $('#edititem .parent select option[class*=-'+ID+'-]').attr('disabled', 'disabled');
                                        $('#edititem .parent select option[value='+ID+']').attr('disabled', 'disabled');
                                    }
                                    endloading();
                                }
                            );
                            loading();
                            return false;
                        });
                        
                        $('#edititem .item-types select, #edititem .item-types input[type=text]').change(function(){
                            var inputs = this.parentNode.getElementsByTagName('input');
                            for(var i=0; i < inputs.length; i++){
                                if(inputs[i].type == 'radio'){
                                    inputs[i].checked = true;
                                }
                            }
                        });
                        
                        $('#edititem').submit(function(){
                            if(navItemList.currentGroup){
                                document.forms.edititem.elements['item[group]'].value=navItemList.currentGroup;
                                loading();
                                return true;
                            }else{
                                alert('Please select navigation group.');
                                return false;
                            }
                        });
                        
                        navEditForm.newItem();
                    
                }
            );
        }
);

function addNavigationGroup(groupname){
    if(!groupname){
        return false;
    }
    jQuery(
        function($){
            $.post(
                        'index.php',
                        {
                            nav_request:1,
                            action:'add_group',
                            group_name:groupname
                        },
                        function(result){
                            
                            eval('var result = '+result+';');
                            
                            $('#navigation-groups').empty();
                            $('#navigation-groups').append(result.html)
                                
                            selectNavigationGroup(result.group_id);
                            
                            navEditForm.newItem();
                            endloading();
                        }
                    );
            loading();
        }
    );
    return true;
}
function deleteNavigationGroup(id){
    if(!confirm("<?php _e('Delete Group?');?>") || !id){
        return false;
    }
        jQuery(
            function($){
                $.post(
                            '<?php echo admin_url('index.php');?>',
                            {
                                nav_request:1,
                                action:'delete_group',
                                group_id:id
                            },
                            remove_group
                        );
                loading();
            }
        );
        function remove_group(result){
            jQuery(
                function($){
                    if(result){
                        $('#navigation-group-'+result).remove();
                        if(navItemList.currentGroup == id){
                            $('#navigation-items').empty();
                        }
                        navItemList.currentGroup = null;
                    }
                    endloading();
                }
            );
        }
    return true;
}

function selectNavigationGroup(id){
    if(navItemList.currentGroup == id){
        return true;
    }
    
    navItemList.currentGroup = id;
    
    document.forms.edititem.elements['item[group]'].value=id;
    
    jQuery(
        function($){
            $('#navigation-groups li').removeClass("active");
                
            $('#navigation-group-'+id).addClass("active");
            
            $.post(
                        '<?php echo admin_url('index.php');?>',
                        {
                            nav_request:1,
                            action:'get_items',
                            group:id
                        },
                        update_items
                    );
            loading();
            function update_items(result){
                
                eval('var result = '+result+';');
                
                var container = $('#navigation-items');
                container.empty();
                container.append(result.items);
                
                var parents_select = $('#edititem #itemparent');
                
                parents_select.empty();
                parents_select.append(result.parents);
                
                var method = result.usejs;
                
                $('input[name=usejs]').attr('checked', (method ? 'checked' : ''));
                
                $('select[name=usejs_method] option').attr('selected', '');
                $('select[name=usejs_method] option[value='+method+']').attr('selected', 'selected');
                
                endloading();
            }
        }
    );
    navEditForm.newItem();
}
function submitItem(result){
    if(result){
        eval('var result = '+result+';');
        
        if(result.action == 'add'){
            var parent = document.forms.edititem.elements['item[parent]'].value;
            jQuery(
                function($){
                    if(parseInt(parent)){
                        if($('#navigation-item-'+parent+' > ul').get(0)){
                            $('#navigation-item-'+parent+' > ul').append(result.item);
                        }else{
                            $('#navigation-item-'+parent).prepend('<span class="handler" onclick="openclose(this);">&nbsp;</span>');
                            
                            $('#navigation-item-'+parent).append('<ul class="sub-level">'+result.item+'</ul>');
                        }
                    }else{
                        $('#navigation-items').append(result.item);
                    }
                    
                    var parents_select = $('#edititem #itemparent');
                        
                    parents_select.empty();
                    parents_select.append(result.parents);

                    $('#edititem #itemparent option[value='+parent+']').attr('selected', 'selected');
                    
                }
            );
        }else if(result.action == 'update'){
            jQuery(
                function($){
                    $('#navigation-item-'+result.ID+' > a').text(result.name);
                    
                    var parentLI = $('#navigation-item-'+result.ID).parents('li').get(0);
                    
                    var parent_id = parentLI ? parentLI.id : 0;
                    
                    if(parent_id != 'navigation-item-'+result.parent){
                            var item = $('#navigation-item-'+result.ID);
                            
                            if(parseInt(result.parent)){
                                if(!$('#navigation-item-'+result.parent+' > ul').get(0)){
                                    $('#navigation-item-'+result.parent).prepend('<span class="handler" onclick="openclose(this);">&nbsp;</span>');
                                    
                                    $('#navigation-item-'+result.parent).append('<ul class="sub-level"></ul>');
                                    
                                }
                                
                                item.appendTo($('#navigation-item-'+result.parent+' > ul'));
                                
                                
                            }else{
                                if(parent_id != 0){
                                    item.appendTo($('#navigation-items'));
                                }
                            }
                            
                            if(!$('#'+parent_id+' > ul > li').get(0)){
                                    $('#'+parent_id+' > ul').remove();
                                    $('#'+parent_id+' > span.handler').remove();
                            }
                            
                            var parents_select = $('#edititem #itemparent');
                            
                            parents_select.empty();
                            parents_select.append(result.parents);
                            
                            $('#edititem .parent select option[value='+result.parent+']').attr('selected', 'selected');
                            
                            $('#edititem .parent select option[class*=-'+parent_id+'-]').attr('disabled', 'disabled');
                            $('#edititem .parent select option[value='+parent_id+']').attr('disabled', 'disabled');
                    }
                }
            );
        }
        success("<?php _e('Changes saved');?>");
    }
    endloading();
}

function reset_nav_form(){
        jQuery(
            function($){
                $('#edititem input[type=text]').attr('value', '');
                $('#edititem select option').attr('selected', '');
                $('#edititem select option').attr('disabled', '');
                $('#edititem #itemgroup').attr('value', navItemList.currentGroup);
            }
        );
}


function loading(){
    jQuery(
        function($){
            $('.nav-info .indicator').css('visibility','visible')
            var navscreen = $('<div class="screen">&nbsp;</div>');
            
            $('.navigation-manager-container').append(navscreen);
        }
    );
}
function endloading(){
    jQuery(
        function($){
            $('.nav-info .indicator').css('visibility','hidden');
            $('.navigation-manager-container .screen').remove();
        }
    );
}

function success(text){
    if(typeof(show) != 'undefined'){
        clearInterval(show);
    }
    if(typeof(message_timer) != 'undefined'){
        clearTimeout(message_timer);
    }
    jQuery(
        function($){
            var message_block = $('.nav-info .message');
            var opacity = 0;
            
            message_block.css('visibility', 'visible');
            message_block.css('opacity', opacity);
            
            var show = setInterval(
                function(){
                    
                    if(opacity >= 1){
                        clearInterval(show);
                        message_timer = setTimeout(function(){
                            $('.nav-info .message').css('visibility', 'hidden');
                        }, 3000);
                    }
                    message_block.css('opacity', opacity);
                    opacity +=0.2;
                }, 50
            );
            
            
            $('.nav-info .message').addClass('success');
            $('.nav-info .message').text(text);
            
        }
    );
}
function selectitem(id){
    navItemList.select(id);
    jQuery(function($){$('button.addNewItem').css('visibility', 'visible')});
}