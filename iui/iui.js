 /*
 	 Copyright (c) 2007, iUI Project Members
	 See LICENSE.txt for licensing terms
 */
var historyWindow;

(function() {

var slideSpeed = 100;
var slideInterval = 0;

var currentPage = null;
var currentDialog = null;
var currentWidth = 0;
var currentHash = location.hash;
var hashPrefix = "#_";
var pageHistory = [];
var newPageCount = 0;
var checkTimer;
var singleMsg = false;
var lastOpenedMsg = null;

// *************************************************************************************************

window.iui =
{
    showPage: function(page, backwards)
    {
        if (page)
        {
            if (currentDialog)
            {
                currentDialog.removeAttribute("selected");
                currentDialog = null;
            }

            if (hasClass(page, "dialog"))
                showDialog(page);
            else
            {
                var fromPage = currentPage;
                currentPage = page;

                if (fromPage)
                    setTimeout(slidePages, 0, fromPage, page, backwards);
                else
                    updatePage(page, fromPage);
            }

            trackPageView(page.id);
            var backHref = page.getAttribute("backHref");
            if (backHref) {
                $('backButton').setAttribute("href", backHref);
            }

            
        }
    },

    showPageById: function(pageId)
    {
        var page = $(pageId);
        if (page)
        {
            var index = pageHistory.indexOf(pageId);
            var backwards = index != -1;
            if (backwards)
                pageHistory.splice(index, pageHistory.length);

            iui.showPage(page, backwards);
        }
    },

    showPageByHref: function(href, args, method, replace, cb)
    {
        var req = new XMLHttpRequest();
        req.onerror = function()
        {
            if (cb)
                cb(false);
        };
        
        req.onreadystatechange = function()
        {
            if (req.readyState == 4)
            {
                if (replace) {
                    //replaceElementWithSource(replace, req.responseText);
                    replace.innerHTML = req.responseText;
                    try {
                        trackPageView(replace.getElementsByTagName("div")[0].id);
                    } catch (c) {}
                    //replace.removeAttribute("loading");
                }
                else
                {
                    var frag = document.createElement("div");
                    frag.innerHTML = req.responseText;
                    iui.insertPages(frag.childNodes);
                    
                }
                if (cb)
                    setTimeout(cb, 1000, true);
            }
        };

        if (args)
        {
            req.open(method || "GET", href, true);
            req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            req.send(args.join("&"));
        }
        else
        {
            if (href.indexOf('&r=1') == -1) {
                parent.historyframe.location.href = href;
                href += "&r=1";
            }
            req.open(method || "GET", href, true);
            req.send(null);
        }
    },
    
    insertPages: function(nodes)
    {
        var targetPage;
        for (var i = 0; i < nodes.length; ++i)
        {
            var child = nodes[i];
            if (child.nodeType == 1)
            {
                if (!child.id)
                    child.id = "__" + (++newPageCount) + "__";

                var clone = $(child.id);
                if (clone)
                    clone.parentNode.replaceChild(child, clone);
                else
                    document.body.appendChild(child);

                if (child.getAttribute("selected") == "true" || !targetPage)
                    targetPage = child;
                
                --i;
            }
        }

        if (targetPage)
            iui.showPage(targetPage);    
    },

    getSelectedPage: function()
    {
        for (var child = document.body.firstChild; child; child = child.nextSibling)
        {
            if (child.nodeType == 1 && child.getAttribute("selected") == "true")
                return child;
        }    
    }    
};

// *************************************************************************************************

addEventListener("load", function(event)
{
    var page = iui.getSelectedPage();
    if (page)
        iui.showPage(page);

    setTimeout(preloadImages, 0);
    setTimeout(checkOrientAndLocation, 0);
    checkTimer = setInterval(checkOrientAndLocation, 300);
    
    // getting user data from cookie and setting it in the fields
    var username = getCookie("man_user");
    var password = getCookie("man_pass");
    var paranoia = getCookie("man_paranoia");
    var singlemsg = getCookie("man_singlemsg");
    
    $('username').value = username;
    $('password').value = password;
    $('post_nick').value = username;
    $('post_pass').value = password;
    
    if (paranoia == '1') $('paranoia').checked = "true";
    if (singlemsg == '1') { 
        $('singlemsg').checked = "true";
        singleMsg = true;
    }
    
}, false);
    
addEventListener("click", function(event)
{
    var link = findParent(event.target, "a");
   
    if (link)
    {
        function unselect() { link.removeAttribute("selected"); }
        
        if (link.href && link.hash && link.hash != "#")
        {
            link.setAttribute("selected", "true");
            iui.showPage($(link.hash.substr(1)));
            setTimeout(unselect, 500);
        }
/*        else if (link == $("backButton") && link.hash != "#")
            history.back();*/
        else if (link.getAttribute("type") == "storeSettings")
        {
            //alert("setting username and password to " + $('username').value + " and " + $('password').value);
            setCookie("man_user", $('username').value, 365);
            setCookie("man_pass", $('password').value, 365);
            setCookie("man_paranoia", $('paranoia').checked ? $('paranoia').value : '0', 365);
            setCookie("man_singlemsg", $('singlemsg').checked ? $('singlemsg').value : '0', 365);
            
            singleMsg = $('singlemsg').checked;
            
            $('post_nick').value = $('username').value;
            $('post_pass').value = $('password').value;
            
            cancelDialog(findParent(link, "form"));
        }
        else if (link.getAttribute("type") == "submit")
            submitForm(findParent(link, "form"));
        else if (link.getAttribute("type") == "cancel") 
        {
            cancelDialog(findParent(link, "form"));
        }
        else if (link.getAttribute("type") == "messagedetail") 
        {
            var containingLi = findParent(link, "li");
            
            // if the message is being displayed, hide it.
            if (containingLi.lastChild.localName != null && 
                hasClass(containingLi.lastChild, "message")) 
            {
                containingLi.removeChild(containingLi.lastChild);
                containingLi.removeAttribute("opened");
            }
            else 
            {
                if (singleMsg && lastOpenedMsg != null) {
                    try {
                        lastOpenedMsg.removeChild(lastOpenedMsg.lastChild);
                    } catch (c) {
                        console.log(c);
                    }
                    lastOpenedMsg.removeAttribute("opened");
                }
                // create a div to contain the data
                containingLi.setAttribute("opened", "true");
                var msgdiv = document.createElement("div");
                msgdiv.setAttribute("class", "message");
                msgdiv.setAttribute("loading", "true");
                
                containingLi.appendChild(msgdiv);
                
                msgdiv.setAttribute("style", 'margin-left:-' + (containingLi.offsetLeft + 11) + 'px');
                lastOpenedMsg = containingLi;

				// scroll the message into view
//                containingLi.scrollIntoView(true);
                
                // load the data
                iui.showPageByHref(link.href, null, null, msgdiv, function() {
                        checkIfClosed(msgdiv);
                        msgdiv.removeAttribute("loading");
                        containingLi.scrollIntoView(true);
                    });
                
            }
        }
        else if (link.target == "_replace")
        {
            link.setAttribute("selected", "progress");
            iui.showPageByHref(link.href, null, null, link, unselect);
        }
        else if (!link.target)
        {
            link.setAttribute("selected", "progress");
            iui.showPageByHref(link.href, null, null, null, unselect);
        }
        else
            return;
        
        event.preventDefault();        
    }


}, true);

addEventListener("click", function(event)
{
    var div = findParent(event.target, "div");
    if (div && hasClass(div, "toggle"))
    {
        div.setAttribute("toggled", div.getAttribute("toggled") != "true");
        event.preventDefault();        
    }
}, true);

function checkOrientAndLocation()
{
    if (window.innerWidth != currentWidth)
    {   
        currentWidth = window.innerWidth;
        var orient = currentWidth == 320 ? "profile" : "landscape";
        document.body.setAttribute("orient", orient);
		document.body.appendChild( document.createTextNode('') );
		document.body.removeChild( document.body.lastChild );
        setTimeout(parent.scrollTo, 100, 0, 1);
    }

    if (location.hash != currentHash)
    {
        var pageId = location.hash.substr(hashPrefix.length)
        iui.showPageById(pageId);
    }
//	alert( document.body.getAttribute("orient"));
}

function showDialog(page)
{
//	alert("should show dialog " + page);
    currentDialog = page;
    page.setAttribute("selected", "true");
//	page.setAttribute("style", "display:block");
    
    if (hasClass(page, "dialog") && !page.target)
        showForm(page);

}

function showForm(form)
{
    form.onsubmit = function(event)
    {
        event.preventDefault();
        submitForm(form);
    };
    
/*
    form.onclick = function(event)
    {
        if (event.target == form && hasClass(form, "dialog"))
            cancelDialog(form);
    };
*/
}

function cancelDialog(form)
{
//	form.setAttribute("style", "display:none");
    form.removeAttribute("selected");
}

function updatePage(page, fromPage)
{
    if (!page.id)
        page.id = "__" + (++newPageCount) + "__";

    location.href = currentHash = hashPrefix + page.id;
    pageHistory.push(page.id);

    var pageTitle = $("pageTitle");

    var refreshUrl = page.getAttribute("refreshUrl");
    if (refreshUrl && refreshUrl != "") {
        pageTitle.innerHTML = "<a href=\"" + refreshUrl + "\" type=\"reload\">" + page.title + "</a>";
    } else {
        if (page.title)
            pageTitle.innerHTML = page.title;
    }


    if (page.localName.toLowerCase() == "form" && !page.target)
        showForm(page);
        
    var backButton = $("backButton");
    if (backButton)
    {
        var prevPage = $(pageHistory[pageHistory.length-2]);
        if (prevPage && !page.getAttribute("hideBackButton"))
        {
            backButton.style.display = "inline";
            backButton.innerHTML = prevPage.title ? prevPage.title : "Back";

            var backText = page.getAttribute("backText");
            if (backText)
                $('backButton').innerHTML = backText;
        }
        else
            backButton.style.display = "none";
    }    
}

function slidePages(fromPage, toPage, backwards)
{        
    var axis = (backwards ? fromPage : toPage).getAttribute("axis");
    if (axis == "y")
        (backwards ? fromPage : toPage).style.top = "100%";
    else
        toPage.style.left = "100%";

    toPage.setAttribute("selected", "true");
    parent.scrollTo(0, 1);
    clearInterval(checkTimer);
    
    var percent = 100;
    slide();
    var timer = setInterval(slide, slideInterval);

    function slide()
    {
        percent -= slideSpeed;
        if (percent <= 0)
        {
            percent = 0;
            if (!hasClass(toPage, "dialog"))
                fromPage.removeAttribute("selected");
            clearInterval(timer);
            checkTimer = setInterval(checkOrientAndLocation, 300);
            setTimeout(updatePage, 0, toPage, fromPage);
        }
    
        if (axis == "y")
        {
            backwards
                ? fromPage.style.top = (100-percent) + "%"
                : toPage.style.top = percent + "%";
        }
        else
        {
            fromPage.style.left = (backwards ? (100-percent) : (percent-100)) + "%"; 
            toPage.style.left = (backwards ? -percent : percent) + "%"; 
        }
    }
}

function preloadImages()
{
    var preloader = document.createElement("div");
    preloader.id = "preloader";
    document.body.appendChild(preloader);
}

function submitForm(form)
{
    iui.showPageByHref(form.action || "POST", encodeForm(form), form.method);
}

function encodeForm(form)
{
    function encode(inputs)
    {
        for (var i = 0; i < inputs.length; ++i)
        {
            if (inputs[i].name)
                args.push(encodeURIComponent(inputs[i].name) + "=" + encodeURIComponent(inputs[i].value));
        }
    }

    var args = [];
    encode(form.getElementsByTagName("input"));
    encode(form.getElementsByTagName("select"));
    encode(form.getElementsByTagName("textarea"));
    return args;    
}

function findParent(node, localName)
{
    while (node && (node.nodeType != 1 || node.localName.toLowerCase() != localName))
        node = node.parentNode;
    return node;
}

function hasClass(self, name)
{
    var re = new RegExp("(^|\\s)"+name+"($|\\s)");
    return re.exec(self.getAttribute("class")) != null;
}

function replaceElementWithSource(replace, source)
{
    var page = replace.parentNode;
    var parent = replace;
    while (page.parentNode != document.body)
    {
        page = page.parentNode;
        parent = parent.parentNode;
    }

    var frag = document.createElement(parent.localName);
    frag.innerHTML = source;

    page.removeChild(parent);

    while (frag.firstChild)
        page.appendChild(frag.firstChild);
}

function $(id) { return document.getElementById(id); }
function ddd() { console.log.apply(console, arguments); }

function setCookie(c_name,value,expiredays)
{
    var exdate=new Date();
    exdate.setDate(exdate.getDate()+expiredays);
    document.cookie=c_name+ "=" +escape(value)+
        ((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
}
function getCookie(c_name)
{
    if (document.cookie.length>0)
    {
        c_start=document.cookie.indexOf(c_name + "=");
        if (c_start!=-1)
        { 
            c_start=c_start + c_name.length+1; 
            c_end=document.cookie.indexOf(";",c_start);
            if (c_end==-1) c_end=document.cookie.length;
            return unescape(document.cookie.substring(c_start,c_end));
        } 
    }
    return "";
}

})();
function trackPageView(page) {
    try {
        // track the page view with google analytics!
        page = page.replace(/:/g, "/");
        console.log("tracking /" + page + " with google analytics");
        pageTracker._trackPageview("/" + page);
    } catch (c) {}
}
function checkIfClosed(caller) {
    var theMessageDiv = caller.getElementsByTagName('div')[0];
    // extracting the id of the page from the id of the message
    var pageId = theMessageDiv.id.substring(0, theMessageDiv.id.indexOf(':message'));
    var closed = document.getElementById(pageId).getAttribute("closed");
    if (closed == "1")
        theMessageDiv.getElementsByTagName('div')[1].setAttribute("style", "display:none;");
}

function fillForm(caller,board,message) {

    document.getElementById('post_brdid').value = board;
    document.getElementById('post_msgid').value = message;

    var messageContainer = caller.parentNode.parentNode;
    var title = html_entity_decode(messageContainer.getElementsByTagName('div')[2].innerHTML);
    var msg = messageContainer.getElementsByTagName('div')[3].innerHTML;
    
    if (title.substring(0,3) != 'Re:')
        title = "Re:" + title;
    if (title.length > 56) 
        title = title.substring(0,56);
    document.getElementById('post_subject').value = title;
    // resetting the size of the textarea
    document.getElementById('post_body').setAttribute('style', 'height:150px');
    if (msg != '') {
        msg = msg.replace(/(<([^>]+)>)/ig,"")
        msg = msg.replace(/<br>/g, "");
        msg = msg.replace(/\n/g, "\n>");
        msg = msg.replace(/&gt;/g, ">");
        msg = ">" + msg;
        var post_body = document.getElementById('post_body');
        post_body.value = msg.replace(/^\s+|\s+$/g,"") + "\n";
		post_body.setAttribute('style', 'height:' + (post_body.scrollHeight + 30) + 'px');
		
		// position the cancel and post buttons
//		document.getElementById('post_cancelbutton').setAttribute("style", "top:" + (post_body.scrollHeight + 170) + "px");
//		document.getElementById('post_submitbutton').setAttribute("style", "top:" + (post_body.scrollHeight + 170) + "px");
    }
}
function fillFormAfterError(board, message, subject, body) {
    document.getElementById('post_brdid').value = board;
    document.getElementById('post_msgid').value = message;
    document.getElementById('post_subject').value = subject;
    // resetting the size of the textarea
    document.getElementById('post_body').setAttribute('style', 'height:150px');

    var post_body = document.getElementById('post_body');
    post_body.value = body;
	post_body.setAttribute('style', 'height:' + (post_body.scrollHeight + 30) + 'px');
		
	// position the cancel and post buttons
//	document.getElementById('post_cancelbutton').setAttribute("style", "top:" + (post_body.scrollHeight + 170) + "px");
//	document.getElementById('post_submitbutton').setAttribute("style", "top:" + (post_body.scrollHeight + 170) + "px");
}


function html_entity_decode(str) {
  var tarea=document.createElement('textarea'); // the "content" part is needed in buttons
  tarea.innerHTML = str; return tarea.value;
  tarea.parentNode.removeChild(tarea);
}

function spoiler(obj) {
   if (obj.nextSibling.style.display === 'none') {
       obj.nextSibling.style.display = 'inline';
   }
   else {
       obj.nextSibling.style.display = 'none';
   }
}
window.spoiler = spoiler;