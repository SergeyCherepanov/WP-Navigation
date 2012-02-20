/**
 * Navigation Manager - Wordpress Plugin.
 *
 * @copyright:    Copyright 2008 Sergey Cherepanov. (http://www.cherepanov.org.ua)
 * @author:       Sergey Cherepanov (sergey@cherepanov.org.ua)
 * @license:      http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE v3.0
 * @date          16.12.08
 */

function openclose(e)
{
    if(e.parentNode.tagName == 'LI') {
        var sublevel = e.parentNode.getElementsByTagName('ul')[0];
    } else if(e.tagName == 'LI') {
        var sublevel = e.getElementsByTagName('ul')[0];
    } else {
        return false;
    }
    
    if (sublevel.className.search('open') == -1) {
        changeClassName(sublevel, 'close', 'open');
        changeClassName(e, 'close', 'open');
    } else {
        changeClassName(sublevel, 'open', 'close');
        changeClassName(e, 'open', 'close');
    }
}

function changeClassName(element, first, last)
{
    if (element.className.indexOf(first) != -1) {
            element.className = element.className.replace(first, last);
    } else {
        element.className += ' '+last+' ';
    }
}