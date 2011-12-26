/**
 * GlobalQuran Online Quran project
 * @author Basit (i@basit.me || http://Basit.me)
 *
 * Haste Static Resource Management Tool 
 * http://imegah.com/blog/facebook-like-haste-static-resource-management-tool
 *
 * Copyright 2011, imegah.com
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this 
 * software and associated documentation files (the ‘Software’), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify, merge, 
 * publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons 
 * to whom the Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all copies or 
 * substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED ‘AS IS’, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING 
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, 
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, 
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE 
 */
/**
 * loads javascript and css on fly.
 * 
 * @param filename path of the javascript or css file
 * @param filetype js or css
 * @param id id must start with letters
 * @param [callback]
 */
function load_resource (filename, filetype, id, callback)
{
    if (filetype == "js")
    { //if filename is a external JavaScript file
	    var fileref = document.createElement('script');
	    fileref.setAttribute("type", "text/javascript");
	    fileref.setAttribute("src", filename);
    }
    else
    {
    	//if filename is an external CSS file
	    var fileref = document.createElement("link");
	    fileref.setAttribute("rel", "stylesheet");
	    fileref.setAttribute("type", "text/css");
	    fileref.setAttribute("href", filename);
    }
    
    if (typeof callback != "undefined")
    	fileref.onload = callback;
    
    if (typeof fileref != "undefined")
    {
    	fileref.setAttribute("id", id);
    	document.getElementsByTagName("head")[0].appendChild(fileref)
    }
}

/**
 * unload resource 
 * [caution] javascript resources will still be in memory browser
 * @param id
 */
function unload_resource (id)
{
	 var head = document.getElementsByTagName('head').item(0);
	 var resource = document.getElementById(id);
	 resource.parentNode.removeChild(resource);
}