User Agent Generator
===============================

Generate User-Agent strings, roughly based on browser and OS 
usage statistics.

Numbers for more minor variable components of the User-Agent for each browser 
are chosen randomly, so it may at times generate User-Agents that never actually 
exist in the wild.


Usage
-----

Simply require `uagent.php` and call the function `UAgent::random()`. You may
optionally pass a single parameter consisting of an array of language codes to
randomly choose from.


Browsers
---------

* Chrome
* Firefox: [Gecko User Agent String Reference](https://developer.mozilla.org/en-US/docs/Web/HTTP/Gecko_user_agent_string_reference)
* Internet Explorer: [Understanding User-Agent Strings](http://msdn.microsoft.com/en-gb/library/ms537503(v=vs.85).aspx)
* Safari
* Opera: [Opera User Agent Strings: Opera 15 and Beyond](http://dev.opera.com/blog/opera-user-agent-strings-opera-15-and-beyond/)


Known Limitations
---------

Generated User-Agent strings are based on formats of valid User-Agents found in the wild but minor variable components such as version numbers aren't always REAL(TM), but they should be well-formed and therefore look realistic.


Credits
---------
* Luka Pusic: [lukapusic/random-uagent](https://github.com/lukapusic/random-uagent)
* Michael White: [mwhite/random-uagent](https://github.com/mwhite/random-uagent)


Statistics (Updated: August 2014)
---------

* [Web Browsers](http://en.wikipedia.org/wiki/Usage_share_of_web_browsers) (Wikipedia)
* [Operating Systems](http://en.wikipedia.org/wiki/Usage_share_of_operating_systems) (Wikipedia)
* [Operating Systems Market Share](http://statowl.com/operating_system_market_share_by_os_version.php) (StatOWL)


License
-------

"THE BEER-WARE LICENSE" (Revision 42):

<pusic93@gmail.com> wrote this file. As long as you retain this notice you can
do whatever you want with this stuff. If we meet some day, and you think this
stuff is worth it, you can buy me a beer in return. Luka Pusic
