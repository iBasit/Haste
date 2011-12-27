/**
 * Haste Static Resource Management Tool
 * http://imegah.com/blog/facebook-like-haste-static-resource-management-tool
 * 
 * @author Basit (i@basit.me || http://Basit.me)
 * 
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

var Bootloader = {
	map: {},
	
	/**
	 * loads javascript and css on fly.
	 * 
	 * @param id id must start with letters
	 * @param [path] path of the javascript or css file
	 * @param [type] js or css
	 * @param [callback]
	 */
	load: function (id, path, type, callback)
	{
		path = path || this.map[id][src];
		type = type || this.map[id][type];
		
	    if (type == "js")
	    { //if filename is a external JavaScript file
		    var code = document.createElement('script');
		    code.setAttribute("type", "text/javascript");
		    code.setAttribute("src", path);
	    }
	    else
	    {
	    	//if filename is an external CSS file
		    var code = document.createElement("link");
		    code.setAttribute("rel", "stylesheet");
		    code.setAttribute("type", "text/css");
		    code.setAttribute("href", path);
	    }
	    
	    if (typeof callback != "undefined")
	    	code.onload = callback;
	    
	    if (typeof code != "undefined")
	    {
	    	code.setAttribute("id", id);
	    	document.getElementsByTagName("head")[0].appendChild(code);
	    }
	},
	
	/**
	 * unload resource 
	 * [caution] javascript resources will still be in memory browser
	 * @param id
	 */
	unload: function (id)
	{
		 var head = document.getElementsByTagName('head').item(0);
		 var resource = document.getElementById(id);
		 resource.parentNode.removeChild(resource);
	},
	
	/**
	 * adding more resources, just pass the object.
	 * @param addResource object
	 */
	setResourceMap: function (addResource)
	{
		for (var id in addResource)
		{
			this.map[id] = addResource[id];
		}
	},
	
	/**
	 * 
	 * @param idArray
	 */
	pagelet: function (idArray)
	{
		for (var i = 0; i < idArray.length; i++)
		{
			alert(idArray[i]);
			id = idArray[i];
			this.load(id);
		}
	}
}; 