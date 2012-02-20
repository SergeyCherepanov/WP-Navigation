/**
 * Navigation Manager - Wordpress Plugin.
 *
 * @copyright:    Copyright 2008 Sergey Cherepanov. (http://www.cherepanov.org.ua)
 * @author:       Sergey Cherepanov (sergey@cherepanov.org.ua)
 * @license:      http://www.gnu.org/licenses/gpl.html GNU GENERAL PUBLIC LICENSE v3.0
 * @date          16.12.08
 */
function upelement(e)
{
    var current     = e;
    var parent      = e.parentNode;
    var previous    = current;
    
    while (previous = previous.previousSibling) {
        if (previous.nodeType == 1) {
            break;
        }
    }
    if (current && previous) {
        var tempPrevious        = parent.appendChild(previous.cloneNode(true));
        var tempCurrent         = parent.appendChild(current.cloneNode(true));
        //tempPrevious.className   = current.className;
        //tempCurrent.className    = previous.className;
        
        parent.replaceChild(tempCurrent, previous);
        parent.replaceChild(tempPrevious, current);
    }
}

function downelement(e)
{
    var current = e;
    var parent  = e.parentNode;
    var next    = current;
    while (next = next.nextSibling) {
        if (next.nodeType == 1) {
            break;
        }
    }
    if (current && next) {
        var tempnext    = parent.appendChild(next.cloneNode(true));
        var tempCurrent = parent.appendChild(current.cloneNode(true));
        //tempnext.className       = current.className;
        //tempCurrent.className    = next.className;
        
        parent.replaceChild(tempCurrent, next);
        parent.replaceChild(tempnext, current);
    }
}