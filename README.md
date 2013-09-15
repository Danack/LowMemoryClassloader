LowMemoryClassloader
====================

A PHP Composer compatible classloader that uses less memory.


Why
===

I'm an ex-video games developer. I can't stand seeing huge chunks of memory being used when it isn't necessary.

After running `composer--optimize-autoloader update` on my current project, the 'optimised' version of the class loader looks like this: http://pastebin.com/Xa1ii7PY which quite frankly appalls. It takes up 645kB of memory, which is about 30% of the memory needed to display a page in the framework I use.

Although some sort of optimization for figuring out where the files for classes exist is needed (as file_exists() calls are too darn slow, and must be avoided at all costs) we really ought to do better than using up that much memory.

Also, Composer does not cache where the files for classes are that it doesn't install. E.g. all generated classes that you use in a project have a `file_exists()` call made for each request. The LowMemoryClassloader caches this away.

How it works
============

OPCache stores optimized versions of PHP files. We can use that as a cache of which files have already been loaded in a previous PHP script, which effectively gives us a cache of what files contain which classes from a small list of available possibilities.

So instead of storing a massive list of where all the individual file for classes are, we can just use the much shorter list of top-level namespaces (from the file autoload_namespaces.php) to search for files once, and then, effectively, cache the result through OPCache.

The only time when the full classmap file is loaded is when PHP tries to autoload a class that isn't cached by OPCache, and isn't namespaced.

The LowMemoryClassloader requires you to use my fork of OPCache from https://github.com/Danack/ZendOptimizerPlus as it adds the function `opcache_script_cached($scriptName)` to allow you to check if a script is cached in OPCache.


How you use it
==============

* Compile in the modified version of OPCache.

* In your PHP script wherever you include the Composer classloader `require_once('../vendor/autoload.php');` replace it with the LowMemoryClassLoader `require_once('../vendor/intahwebz/lowmemoryclassloader/LowMemoryClassloader.php');`

* That's it.


Behaviour difference to Composer
================================

The only known difference in behaviour to the Composer autoloader is that when the LowMemoryClassloader fails to load the file for a class, it does not cache that fact, and will try to find the class again, on the next attempt to load it. This is the correct behaviour IMHO, as it makes runtime code generation be easier to handle.
 
TODO
====

Benchmark this compared to the classloader that ships with Composer. It should be significantly faster on machines that are under at least a medium amount of load. It will definitely be faster for applications that use dynamically generated classes which have a high overhead with Composer's classloader.

However I'm running PHP5.5 in a VM on my laptop, and it's performance is wildly divergent from a real server, so I'm not going to say any benchmarks which I know are unreliable.