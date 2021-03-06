<?PHP
#################################################################################
## Developed by Manifest Interactive, LLC                                      ##
## http://www.manifestinteractive.com                                          ##
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ ##
##                                                                             ##
## THIS SOFTWARE IS PROVIDED BY MANIFEST INTERACTIVE 'AS IS' AND ANY           ##
## EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE         ##
## IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR          ##
## PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL MANIFEST INTERACTIVE BE          ##
## LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR         ##
## CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF        ##
## SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR             ##
## BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,       ##
## WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE        ##
## OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,           ##
## EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.                          ##
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ ##
## Author of file: Peter Schmalfeldt                                           ##
#################################################################################

/**
 * @category Memcache Manager
 * @package MemcacheManager
 * @author Peter Schmalfeldt <manifestinteractive@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://code.google.com/p/memcachemanager/
 * @link http://groups.google.com/group/memcachemanager
 */

/**
 * Begin Document
 */

class MemcacheManager {
	
	/**
     * Main Memcache Object
     *
     * @var    int
     * @access private
     */

	private $mc;
	
	/**
     * Encryption Key
     *
     * @var    int
     * @access private
     */

	private $keypass;
	
	/**
     * Memcache Servers
     *
     * @var    array
     * @access private
     */

	private $servers;
	
	
	/**
     * Constructor
	 *
	 * Create Memcache Manager
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 *  
	 * $data = array(
	 * 	'username'=>'memcachelover',
	 *		'email'=>'me@myemail.com',
	 * 	  	'displayname'=>'Memcache Lover',
	 * 	  	'location'=>array(
	 * 			'country'=>'USA',
	 * 			'state'=>'Oregon',
	 * 			'city'=>'Portland'
	 * 	  	)
	 * );
	 *  
	 * $mc->add('memcachelover', $data, true, true, true);	// adds the key with JSON encoding, encryption and compression
	 * echo $mc->get('memcachelover', false, false, true);	// would echo the uncompressed, but still encrypted key
	 * echo $mc->get('memcachelover', false, true, true);	// would echo the uncompressed, decrypted JSON formatted string
	 * print_r($mc->get('memcachelover',true, true, true));	// would print the uncompressed, decrypted array
	 * print_r($mc->statistics());							// this would print the array of usage stats for all servers
	 * echo $mc->report();									// this would print our custom report
	 * $mc->flushmc();										// this would fluch all connected servers
	 * $mc->close();										// this would close all connections
	 * ?>
 	 * </code>
     *
     * @access 	public
     */	
	public function __construct(){
		$this->servers = array();
		$this->keypass = 'mY*$3Cr3t^p4$sw0Rd';  // change this
		
		if(class_exists('Memcache')) $this->mc = new Memcache;
		else trigger_error("PHP Class 'Memcache' does not exist!", E_USER_ERROR);
	}

	/**
     * Callback Handler for failed connection
     *
     * @param string $host Point to the host where memcached is listening for connections. This parameter may also specify other transports like unix:///path/to/memcached.sock  to use UNIX domain sockets, in this case port  must also be set to 0. 
	 * @param int $port Point to the port where memcached is listening for connections. This parameter is optional and its default value is 11211. Set this parameter to 0 when using UNIX domain sockets.  
     * 
     * @author Peter Schmalfeldt
	 * @version 1.0
     */
	static function _failure($host, $port) {
		var_dump(debug_backtrace());
		trigger_error("Memcache '$host:$port' Failed", E_USER_ERROR);
	}
	
	/**
     * Add a memcached server to connection pool
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * ?>
 	 * </code>
     *
     * @param string $host Point to the host where memcached is listening for connections. This parameter may also specify other transports like unix:///path/to/memcached.sock  to use UNIX domain sockets, in this case port  must also be set to 0. 
	 * @param int $port Point to the port where memcached is listening for connections. This parameter is optional and its default value is 11211. Set this parameter to 0 when using UNIX domain sockets. 
     * @access public
     */
	public function addserver($host, $port){
		$this->servers[] = array('host'=>$host, 'port'=>$port);
			
		// add server in offline mode
		if(function_exists('memcache_add_server')) $this->mc->addServer($host, $port, false, 1, 1, -1, false);
		else trigger_error("PHP Function 'memcache_add_server' does not exist!", E_USER_ERROR);
		
		// place server online with some added parameters to control it
		if($this->status($host, $port)==0){
			if(function_exists('memcache_set_server_params')) $this->mc->setServerParams($host, $port, 1, 15, true, array(&$this, '_failure'));
			else trigger_error("PHP Function 'memcache_set_server_params' does not exist!", E_USER_ERROR);
		}
	}
	
	/**
     * Returns a the servers online/offline status
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * if($mc->status()>0) echo 'Connection Established';
	 * ?>
 	 * </code>
     *
     * @param string $host Point to the host where memcached is listening for connections. This parameter may also specify other transports like unix:///path/to/memcached.sock  to use UNIX domain sockets, in this case port  must also be set to 0. 
	 * @param int $port Point to the port where memcached is listening for connections. This parameter is optional and its default value is 11211. Set this parameter to 0 when using UNIX domain sockets. 
     * @access public
	 * @return array Returns 0 if server is failed, non-zero otherwise 
     */
	public function status($host, $port){
		if(function_exists('memcache_get_server_status')) return $this->mc->getServerStatus($host, $port);
		else trigger_error("PHP Function 'memcache_get_server_status' does not exist!", E_USER_ERROR);
	}
	
	/**
     * Get statistics from all servers in pool
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * print_r($mc->statistics());
	 * ?>
 	 * </code>
     *
     * @access public
	 * @return array Returns a two-dimensional associative array of server statistics or FALSE  on failure. 
     */
	public function statistics(){
		if(function_exists('memcache_get_stats')) return $this->mc->getExtendedStats();
		else trigger_error("PHP Function 'memcache_get_stats' does not exist!", E_USER_ERROR);
	}
	
	/**
     * Immediately invalidates all existing items but doesn't actually free any resources, it only marks all the items as expired, so occupied memory will be overwritten by new items.
	 * Please note that after flushing, you have to wait about a second to be able to write to Memcached again. If you don't, your data is not saved.
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * $mc->flushmc();
	 * ?>
 	 * </code>
     *
     * @access public
     */
	public function flushmc(){
		if(function_exists('memcache_flush')) $this->mc->flush();
		else trigger_error("PHP Function 'memcache_flush' does not exist!", E_USER_ERROR);
		sleep(1); // pause other commands so this has time to finish...
	}
	
   	/**
     * Stores variable $var with $key ONLY if such key doesn't exist at the server yet.
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * $mc->add('mykey1', $myarray1, 30, true, true, true);
	 * $mc->add('mykey2', $myarray2, 30, true, true);
	 * $mc->add('mykey3', $myarray3, 30, true);
	 * $mc->add('mykey4', $myarray4, 30);
	 * $mc->add('mykey5', $myarray5);
	 * ?>
 	 * </code>
     *
     * @param string $key The key that will be associated with the item
	 * @param string $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
	 * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
	 * @param bool $json Whether to encode using JSON 
	 * @param bool $encrypt Whether to encrypt string
	 * @param bool $zlib Use MEMCACHE_COMPRESSED to store the item compressed (uses zlib). 
     * @access public
     */
	public function add($key, $var, $expire=0, $json=false, $encrypt=false, $zlib=false){
		if($zlib) $zlib = MEMCACHE_COMPRESSED;
		if($json) $var = json_encode($var);
		if($encrypt) $var = $this->encrypt($var);
		if(function_exists('memcache_add')) $this->mc->add($key, $var, $zlib, $expire);
		else trigger_error("PHP Function 'memcache_add' does not exist!", E_USER_ERROR);
	}
	
   	/**
     * Stores an item var with key  on the memcached server
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * $mc->set('mykey1', $myarray1, 30, true, true, true);
	 * $mc->set('mykey2', $myarray2, 30, true, true);
	 * $mc->set('mykey3', $myarray3, 30, true);
	 * $mc->set('mykey4', $myarray4, 30);
	 * $mc->set('mykey5', $myarray5);
	 * ?>
 	 * </code>
     *
     * @param string $key The key that will be associated with the item
	 * @param string $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
	 * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
	 * @param bool $json Whether to encode using JSON 
	 * @param bool $encrypt Whether to encrypt string
	 * @param bool $zlib Use MEMCACHE_COMPRESSED to store the item compressed (uses zlib). 
     * @access public
     */
	public function set($key, $var, $expire=0, $json=false, $encrypt=false, $zlib=false){
		if($zlib) $zlib = MEMCACHE_COMPRESSED;
		if($json) $var = json_encode($var);
		if($encrypt) $var = $this->encrypt($var);
		if(function_exists('memcache_set')) $this->mc->set($key, $var, $zlib, $expire);
		else trigger_error("PHP Function 'memcache_set' does not exist!", E_USER_ERROR);
	}
	
	/**
     * Should be used to replace value of existing item with key
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * $mc->replace('mykey1', $myarray1, 30, true, true, true);
	 * $mc->replace('mykey2', $myarray2, 30, true, true);
	 * $mc->replace('mykey3', $myarray3, 30, true);
	 * $mc->replace('mykey4', $myarray4, 30);
	 * $mc->replace('mykey5', $myarray5);
	 * ?>
 	 * </code>
     *
     * @param string $key The key that will be associated with the item
	 * @param string $var The variable to store. Strings and integers are stored as is, other types are stored serialized.
	 * @param int $expire Expiration time of the item. If it's equal to zero, the item will never expire. You can also use Unix timestamp or a number of seconds starting from current time, but in the latter case the number of seconds may not exceed 2592000 (30 days).
	 * @param bool $json Whether to encode using JSON 
	 * @param bool $encrypt Whether to encrypt string
	 * @param bool $zlib Use MEMCACHE_COMPRESSED to store the item compressed (uses zlib). 
     * @access public
     */
	public function replace($key, $var, $expire=0, $json=false, $encrypt=false, $zlib=false){
		if($zlib) $zlib = MEMCACHE_COMPRESSED;
		if($json) $var = json_encode($var);
		if($encrypt) $var = $this->encrypt($var);
		if(function_exists('memcache_replace')) $this->mc->replace($key, $var, $zlib, $expire);
		else trigger_error("PHP Function 'memcache_replace' does not exist!", E_USER_ERROR);
	}
	
   	/**
     * Retrieve item from the server
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * echo $mc->get('mykey1', true, true, true);
	 * echo $mc->get('mykey2', true, true);
	 * echo $mc->get('mykey3', true);
	 * echo $mc->get('mykey4');
	 * ?>
 	 * </code>
     *
     * @param string $keys The key or array of keys to fetch
	 * @param bool $json Whether to decode using JSON 
	 * @param bool $encrypt Whether to decrypt string
	 * @param bool $zlib Use MEMCACHE_COMPRESSED to store the item compressed (uses zlib). 
     * @access public
     */
	public function get($keys, $json=false, $decrypt=false, $zlib=false){
		if($zlib) $zlib = MEMCACHE_COMPRESSED;
		if(function_exists('memcache_get')) $var = $this->mc->get($keys, $zlib);
		else trigger_error("PHP Function 'memcache_get' does not exist!", E_USER_ERROR);
		if($decrypt) $var = $this->decrypt($var);
		if($json) $var = json_decode($var, true);
		if(function_exists('memcache_get')) return $var;
		else trigger_error("PHP Function 'memcache_get' does not exist!", E_USER_ERROR);
	}
	
	/**
     * Delete item from the server
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * $mc->delete('mykey1', 30);
	 * $mc->delete('mykey2');
	 * ?>
 	 * </code>
     *
     * @param string $key The key associated with the item to delete. 
	 * @param int $timeout Execution time of the item. If it's equal to zero, the item will be deleted right away whereas if you set it to 30, the item will be deleted in 30 seconds.
     * @access public
     */
	public function delete($key, $timeout=0){
		if(function_exists('memcache_delete')) {
			if($this->mc->get($key)) {
				$this->mc->delete($key, $timeout);
				sleep(1); // pause other commands so this has time to finish...
			}
		}
		else trigger_error("PHP Function 'memcache_delete' does not exist!", E_USER_ERROR);
	}
	
	/**
     * Increment item's value
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * $mc->increment('mykey1', 5);
	 * $mc->increment('mykey2');
	 * ?>
 	 * </code>
     *
     * @param string $key Key of the item to increment 
	 * @param int $value Increment the item by value . Optional and defaults to 1. 
     * @access public
     */
	public function increment($key, $value=1){
		if(function_exists('memcache_increment')) $this->mc->increment($key, $value);
		else trigger_error("PHP Function 'memcache_increment' does not exist!", E_USER_ERROR);
	}
	
	/**
     * Decrement item's value
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * $mc->decrement('mykey1', 5);
	 * $mc->decrement('mykey2');
	 * ?>
 	 * </code>
     *
     * @param string $key Key of the item do decrement. 
	 * @param int $value Decrement the item by value . Optional and defaults to 1.  
     * @access public
     */
	public function decrement($key, $value=1){
		if(function_exists('memcache_decrement')) $this->mc->decrement($key, $value);
		else trigger_error("PHP Function 'memcache_decrement' does not exist!", E_USER_ERROR);
	}
	
	/**
     * Get statistics from all servers in pool and generate custom report
	 *
	 * <code>
	 * <?php
	 * $mc = new MemcacheManager();
	 * $mc->addserver('localhost', 11211);
	 * echo $mc->report();
	 * ?>
 	 * </code>
     *
     * @access public
	 * @return string HTML Table of statistics. 
     */
	public function report(){
		$status = $this->statistics();
		
		// show possible issues ?
		$show_issues = true;
		$remaining_memory_warn = 1024;
		
		// colors for report
		$color_title = '4D89F9';
		$color_border = 'E4EDFD';
		$color_subtitle = '999';
		$color_active = '4D89F9';
		$color_inactive = 'C6D9FD';
		$color_header = 'C6D9FD';
		$color_section = 'E4EDFD';
		$color_row1 = 'FFF';
		$color_row2 = 'F7F7F7';
		$color_text1 = '555';
		$color_text2 = '7E94BE';
		$color_text_error = '990000';
		
		// table control
		$rowheight = 20;
		$firstcolwidth = 175;
		
		// add totals for summary
		$server_bytes = array();
		$server_limit_maxbytes = array();
		$total_accepting_conns = 0;
		$total_bytes = 0;
		$total_bytes_read = 0;
		$total_bytes_written = 0;
		$total_cas_badval = 0;
		$total_cas_hits = 0;
		$total_cas_misses = 0;
		$total_cmd_flush = 0;
		$total_cmd_get = 0;
		$total_cmd_set = 0;
		$total_conn_yields = 0;
		$total_connection_structures = 0;
		$total_curr_connections = 0;
		$total_curr_items = 0;
		$total_decr_hits = 0;
		$total_decr_misses = 0;
		$total_delete_hits = 0;
		$total_delete_misses = 0;
		$total_evictions = 0;
		$total_get_hits = 0;
		$total_get_misses = 0;
		$total_incr_hits = 0;
		$total_incr_misses = 0;
		$total_limit_maxbytes = 0;
		$total_listen_disabled_num = 0;
		$total_rusage_system = 0;
		$total_rusage_user = 0;
		$total_servers = 0;
		$total_threads = 0;
		$total_total_connections = 0;
		$total_total_items = 0;

		// get totals first for all servers
		foreach($status as $host=>$data){
			$total_servers += 1;
			foreach($data as $key=>$val){
				if($key=='accepting_conns') $total_accepting_conns += $val;
				if($key=='bytes') {
					$total_bytes += $val;
					$server_bytes[] = $val;
				}
				if($key=='bytes_read') $total_bytes_read += $val;
				if($key=='bytes_written') $total_bytes_written += $val;
				if($key=='cas_badval') $total_cas_badval += $val;
				if($key=='cas_hits') $total_cas_hits += $val;
				if($key=='cas_misses') $total_cas_misses += $val;
				if($key=='cmd_flush') $total_cmd_flush += $val;
				if($key=='cmd_get') $total_cmd_get += $val;
				if($key=='cmd_set') $total_cmd_set += $val;
				if($key=='conn_yields') $total_conn_yields += $val;
				if($key=='connection_structures') $total_connection_structures += $val;
				if($key=='curr_connections') $total_curr_connections += $val;
				if($key=='curr_items') $total_curr_items += $val;
				if($key=='decr_hits') $total_decr_hits += $val;
				if($key=='decr_misses') $total_decr_misses += $val;
				if($key=='delete_hits') $total_delete_hits += $val;
				if($key=='delete_misses') $total_delete_misses += $val;
				if($key=='evictions') $total_evictions += $val;
				if($key=='get_hits') $total_get_hits += $val;
				if($key=='get_misses') $total_get_misses += $val;
				if($key=='incr_hits') $total_incr_hits += $val;
				if($key=='incr_misses') $total_incr_misses += $val;
				if($key=='limit_maxbytes') {
					$total_limit_maxbytes += $val;
					$server_limit_maxbytes[] = $val;
				}
				if($key=='listen_disabled_num') $total_listen_disabled_num += $val;
				if($key=='rusage_system') $total_rusage_system += $val;
				if($key=='rusage_user') $total_rusage_user += $val;
				if($key=='threads') $total_threads += $val;
				if($key=='total_connections') $total_total_connections += $val;
				if($key=='total_items') $total_total_items += $val;
			}
		}
		
		// set image width
		$imagewidth = ($total_servers*25);
		if($imagewidth < 150 && $total_servers > 1) $imagewidth = 150;
		$totalwidth = ($imagewidth+320);
		
		// make text strings and labels ... code only supports up to 26 memcache connections with these labels, if you need more, add labels here (AA, AB ...)
		$alpha = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		$servers = ($total_servers==1) ? 'Connection':'Connections';
		
		// configure totals for graphs
		$imageFreeMemory = round(($total_limit_maxbytes-$total_bytes)/(1024*1024),2);
		$imageUsedMemory = round($total_bytes/(1024*1024),2);
		$imageUsedPercent = round(($total_bytes/$total_limit_maxbytes)*100,2);
		$imageFreePercent = (100-$imageUsedPercent);
		$allocatedMemory = $this->bsize($total_limit_maxbytes);
		$usedMemory = $this->bsize($total_bytes);
		$usedMemoryPercent = round(($total_bytes/$total_limit_maxbytes)*100,2);
		$availableMemory = $this->bsize($total_limit_maxbytes-$total_bytes);
		$chl = '';
		$perc = '';
		$totals = '';
		for($i=0; $i<$total_servers; $i++){
			$totals .= round(($server_bytes[$i]/$server_limit_maxbytes[$i])*100).',';
			$chl .= $alpha[$i]."|";
			$perc .= '100,';
		}
		$chl = rtrim($chl,'|');
		$perc = rtrim($perc,',');
		$totals = rtrim($totals,',');
		
		// start report
		$report = "<div id='memcachereport' style='font-family: arial; width: ".($totalwidth+20)."px; margin: 0 auto;'>
		<h3 style='font-size: 16px; color: #{$color_title}; white-space: nowrap;'>Memache Report &nbsp;&rsaquo;&nbsp; {$total_servers} Server {$servers}</h3>
		<a href='http://www.manifestinteractive.com' style='font-size: 10px; color: #{$color_subtitle}; text-decoration: none;' target='_blank'>Developed by Peter Schmalfeldt of Manifest Interactive, LLC</a>
		<table style='font-size: 12px; width: 100%; border: 1px solid #{$color_border}; border-bottom: 0px;' cellpadding='0' cellspacing='0'>";
		
		$report .= "<tr><td align='center' style='padding: 5px;'><img src='http://chart.apis.google.com/chart?cht=p&amp;chd=t:{$imageFreePercent},{$imageUsedPercent}&amp;chs=320x150&amp;chl=Free%20".str_replace(' ', '%20', $availableMemory)."|Used%20".str_replace(' ', '%20', $usedMemory)."&amp;chco={$color_inactive},{$color_active}' style='float: left;' />";
		if($total_servers>1) $report .= "<img src='http://chart.apis.google.com/chart?cht=bvs&amp;chs=".($total_servers*25)."x150&amp;chd=t:{$totals}|{$perc}&amp;chco={$color_active},{$color_inactive}&amp;chbh=20&amp;chds=0,100&amp;chl={$chl}&amp;chm=N*f*%,{$color_active},0,-1,9' style='float: right;' />";
		$report .= "</td></tr></table>";
		
		// if there is more than one connection, show accumulative summary
		if($total_servers>1){
			
			// check for possible issues
			$total_evictions_display = ($show_issues && $total_evictions > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_evictions)." !</span>":$total_evictions;
			$total_memory_available = ($show_issues && ($total_limit_maxbytes-$total_bytes) < $remaining_memory_warn) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".$this->bsize($total_limit_maxbytes-$total_bytes)." !</span>":$this->bsize($total_limit_maxbytes-$total_bytes);
			$total_get_misses_display = ($show_issues && $total_get_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_get_misses)." !</span>":number_format($total_get_misses);
			$total_delete_misses_display = ($show_issues && $total_delete_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_delete_misses)." !</span>":number_format($total_delete_misses);
			$total_incr_misses_display = ($show_issues && $total_incr_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_incr_misses)." !</span>":number_format($total_incr_misses);
			$total_decr_misses_display = ($show_issues && $total_decr_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_decr_misses)." !</span>":number_format($total_decr_misses);
			$total_cas_misses_display = ($show_issues && $total_cas_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_cas_misses)." !</span>":number_format($total_cas_misses);
			
			// add to report
			$report .= "<table style='font-size: 12px; width: 100%; border: 1px solid #{$color_border};' cellpadding='0' cellspacing='0'>
			<tr><td colspan='2' style='font-size: 14px; background-color: #{$color_header}; padding: 5px;'><b>Accumulative Memcache Report</b></td></tr>
			
			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Server Statistics</b></td></tr>
			<tr style='background-color: #{$color_row1};' title='Total system time for this instance (seconds:microseconds).'><td height='{$rowheight}' align='right' style='color:#{$color_text1}' width='{$firstcolwidth}'>System CPU Usage &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_rusage_system} Seconds</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total user time for this instance (seconds:microseconds).'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>User CPU Usage &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_rusage_user} Seconds</td></tr>
			
			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Memory Usage</b></td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of bytes this server is allowed to use for storage.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Memory Allocation &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($total_limit_maxbytes)."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Current number of bytes used by this server to store items.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Memory In Use &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($total_bytes)."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Current number of bytes available to be used by this server to store items.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Memory Available &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_memory_available}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total number of bytes read by this server from network.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Total Read Memory &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($total_bytes_read)."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Total number of bytes sent by this server to network.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Total Written Memory &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($total_bytes_written)."</td></tr>
			
			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Connection Information</b></td></tr>
			<tr style='background-color: #{$color_row1};' title='Current number of open connections.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Current Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($total_curr_connections)."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total number of connections opened since the server started running.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Total Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($total_total_connections)."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of yields for connections.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Connection Yields &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($total_conn_yields)."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of connection structures allocated by the server.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Connection Structures &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($total_connection_structures)."</td></tr>

			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Memcache Statistics</b></td></tr>
			<tr style='background-color: #{$color_row1};' title='The number of times socket listeners were disabled due to hitting the connection limit.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Listeners Disabled &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_listen_disabled_num}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of valid items removed from cache to free memory for new items.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Evections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_evictions_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Total number of flush requests.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CMD Flush Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cmd_flush}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total number of retrieval requests.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CMD Get Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cmd_get}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Total number of storage requests.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CMD Set Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cmd_set}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of keys that have been compared and swapped, but the comparison (original) value did not match the supplied value.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CAS Bad Value &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cas_badval}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been compared and swapped and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CAS Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cas_hits}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been compared and swapped and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CAS Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cas_misses_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been requested and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Get Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_get_hits}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been requested and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Get Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_get_misses_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been deleted and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Delete Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_delete_hits}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been delete and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Delete Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_delete_misses_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been incremented and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Increment Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_incr_hits}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been incremented and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Increment Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_incr_misses_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been decremented and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Decrement Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_decr_hits}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been decremented and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Decrement Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_decr_misses_display}</td></tr>

			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Item Information</b></td></tr>
			<tr style='background-color: #{$color_row1};' title='Current number of items stored by this instance.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Current Items &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_curr_items}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total number of items stored during the life of this instance.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Total Items &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_total_items}</td></tr>
			</table>";
		}
		
		// show sumamry for each with alpha marker from graph
		$i=0;
		foreach($status as $host=>$data){
			list($host, $port) = split(":", $host, 2);
			
			$letter = ($total_servers>1) ? "[ ".$alpha[$i]." ]&nbsp; ":"";
			$currentUsedMemory = $this->bsize($data['bytes']);
			$currentAvailableMemory = $this->bsize($data['limit_maxbytes']-$data['bytes']);
			$currentUsedPercent = round(($data['bytes']/$data['limit_maxbytes'])*100,2);
			$currentFreePercent = (100-$currentUsedPercent);
			$accept = ($data['accepting_conns']==1) ? 'Yes':'No';
			
			// check for possible issues
			$evictions_display = ($show_issues && $data['evictions'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['evictions'])." !</span>":$data['evictions'];
			$memory_available = ($show_issues && ($data['limit_maxbytes']-$data['bytes']) < $remaining_memory_warn) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".$this->bsize($data['limit_maxbytes']-$data['bytes'])." !</span>":$this->bsize($data['limit_maxbytes']-$data['bytes']);
			$get_misses_display = ($show_issues && $data['get_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['get_misses'])." !</span>":number_format($data['get_misses']);
			$delete_misses_display = ($show_issues && $data['delete_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['delete_misses'])." !</span>":number_format($data['delete_misses']);
			$incr_misses_display = ($show_issues && $data['incr_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['incr_misses'])." !</span>":number_format($data['incr_misses']);
			$decr_misses_display = ($show_issues && $data['decr_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['decr_misses'])." !</span>":number_format($data['decr_misses']);
			$cas_misses_display = ($show_issues && $data['cas_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['cas_misses'])." !</span>":number_format($data['cas_misses']);

			// add to report
			$report .= "<table class='memcachereport' style='font-size: 12px; width: 100%; margin-top: 10px; border: 1px solid #{$color_border};' cellpadding='0' cellspacing='0'>";
			if($total_servers>1) $report .= "<tr><td colspan='2' style='padding: 5px;' align='center'><img src='http://chart.apis.google.com/chart?cht=p&amp;chd=t:{$currentFreePercent},{$currentUsedPercent}&amp;chs=300x75&amp;chl=Free%20".str_replace(' ', '%20', $currentAvailableMemory)."|Used%20".str_replace(' ', '%20', $currentUsedMemory)."&amp;chco={$color_inactive},{$color_active}' /></td></tr>";
			
			$report .= "<tr><td colspan='2' style='font-size: 14px; background-color: #{$color_header}; padding: 5px;'><b>{$letter}{$host} &nbsp; &nbsp;&rsaquo;&nbsp; {$port}</b></td></tr>
			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Server Statistics</b></td></tr>
			
			<tr style='background-color: #{$color_row1};' title='1 or 0 to indicate whether the server is currently accepting connections or not.'><td width='{$firstcolwidth}' align='right' height='{$rowheight}' style='color:#{$color_text1}'>Accepting Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$accept."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Version string of this instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Memcache Version &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['version']."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Process id of the memcached instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Process ID &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['pid']."</td></tr>			
			<tr style='background-color: #{$color_row2};' title='Size of pointers for this host specified in bits (32 or 64).'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Pointer Size &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['pointer_size']."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of worker threads requested.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Threads &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['threads']."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Total system time for this instance (seconds:microseconds).'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>System CPU Usage &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['rusage_system']."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total user time for this instance (seconds:microseconds).'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>User CPU Usage &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['rusage_user']."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Start Time for this memcached instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Start Time &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".date('F jS, Y g:i:sA T',$data['time']-$data['uptime'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Uptime for this memcached instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Uptime &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->duration($data['time']-$data['uptime'])."</td></tr>
			
			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Memory Usage</b></td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of bytes this server is allowed to use for storage.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Memory Allocation &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($data['limit_maxbytes'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Current number of bytes used by this server to store items.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Memory In Use &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($data['bytes'])."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Current number of bytes available to be used by this server to store items.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Memory Available &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$memory_available}</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total number of bytes read by this server from network.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Total Read Memory &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($data['bytes_read'])."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Total number of bytes sent by this server to network.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Total Written Memory &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($data['bytes_written'])."</td></tr>
			
			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Connection Information</b></td></tr>
			<tr style='background-color: #{$color_row1};' title='Current number of open connections.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Current Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['curr_connections'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total number of connections opened since the server started running. 	'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Total Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['total_connections'])."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of yields for connections.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Connection Yields &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['conn_yields'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of connection structures allocated by the server.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Connection Structures &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['connection_structures'])."</td></tr>
			
			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Memcache Statistics</b></td></tr>
			<tr style='background-color: #{$color_row1};' title='The number of times socket listeners were disabled due to hitting the connection limit.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Listeners Disabled &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['listen_disabled_num'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of valid items removed from cache to free memory for new items.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Evections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$evictions_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Total number of flush requests.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CMD Flush Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cmd_flush'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total number of retrieval requests.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CMD Get Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cmd_get'])."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Total number of storage requests.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CMD Set Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cmd_set'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of keys that have been compared and swapped, but the comparison (original) value did not match the supplied value.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CAS Bad Value &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cas_badval'])."</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been compared and swapped and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CAS Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cas_hits'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been compared and swapped and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CAS Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$cas_misses_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been requested and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Get Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['get_hits'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been requested and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Get Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$get_misses_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been deleted and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Delete Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['delete_hits'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been delete and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Delete Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$delete_misses_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been incremented and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Increment Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['incr_hits'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been incremented and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Increment Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$incr_misses_display}</td></tr>
			<tr style='background-color: #{$color_row1};' title='Number of keys that have been decremented and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Decrement Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['decr_hits'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Number of items that have been decremented and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Decrement Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$decr_misses_display}</td></tr>
			
			<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Item Information</b></td></tr>
			<tr style='background-color: #{$color_row1};' title='Current number of items stored by this instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Current Items &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['curr_items'])."</td></tr>
			<tr style='background-color: #{$color_row2};' title='Total number of items stored during the life of this instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Total Items &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['total_items'])."</td></tr>
			
			</table><br />";
			$i++;
		}
		$report .= "</div>";
		
		return $report;
	}
	
	/**
	  * Decrypt String with Key
	  *
	  * The function decryptString()
	  * 
	  * @author Peter Schmalfeldt
	  * @version 1.0
	  * @param string $string String to Decrypt
	  * @return string Decrypted String
	  */
	public function decrypt($string){
		if(function_exists('mcrypt_module_open')){
			$string = base64_decode($string);
			if(!$td = mcrypt_module_open('rijndael-256','','ctr','')) return false;
			$iv = substr($string,0,32);
			$mo = strlen($string)-32;
			$em = substr($string,$mo);
			$string = substr($string,32,strlen($string)-64);
			$mac = $this->pbkdf2($iv.$string,$this->keypass,1000,32);
			if($em!==$mac) return false;
			if(mcrypt_generic_init($td,$this->keypass,$iv)!==0) return false;
			$string = mdecrypt_generic($td,$string);
			$string = unserialize($string);
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			return $string;
		}
		else trigger_error("PHP Function 'mcrypt_module_open' does not exist!", E_USER_ERROR);
	}
	
	/**
	  * Encrypt String with Key
	  *
	  * The function encryptString()
	  * 
	  * @author Peter Schmalfeldt
	  * @version 1.0
	  * @param string $string String to Encrypt
	  * @return string Encoded String
	  */
	public function encrypt($string){
		if(function_exists('mcrypt_module_open')){
			if(!$td = mcrypt_module_open('rijndael-256','','ctr','')) return false;
			$string = serialize($string);
			$iv = mcrypt_create_iv(32, MCRYPT_RAND);
			if(mcrypt_generic_init($td,$this->keypass,$iv)!==0)	return false;
			$string = mcrypt_generic($td,$string);
			$string = $iv.$string;
			$mac = $this->pbkdf2($string,$this->keypass,1000,32);
			$string .= $mac;
			mcrypt_generic_deinit($td);
			mcrypt_module_close($td);
			$string = base64_encode($string);
			return $string;
		}
		else trigger_error("PHP Function 'mcrypt_module_open' does not exist!", E_USER_ERROR);
	}
	
	/** PBKDF2 Implementation (as described in RFC 2898);
	 *
	 *	@param string $p Password
	 *	@param string $s Salt
	 *	@param int $c Iteration count (use 1000 or higher)
	 *	@param int $kl Derived key length
	 *	@param string $a Hash algorithm
	 *	@return  string  derived key
	*/
	public function pbkdf2($p,$s,$c,$kl,$a='sha256'){
		$hl = strlen(hash($a,null,true));
		$kb = ceil($kl/$hl);
		$dk = '';
		for($block=1; $block<=$kb; $block++){
			$ib = $b=hash_hmac($a,$s.pack('N',$block),$p,true);
			for($i=1; $i<$c; $i++) $ib ^= ($b=hash_hmac($a,$b,$p,true));
			$dk .= $ib;
		}
		return substr($dk, 0, $kl);
	}
	
	
	/**
	  * Convert bytes into human readable format
	  * 
	  * @author Peter Schmalfeldt
	  * @version 1.0
	  * @param int $s Size to convert
	  * @return string Size Measurement
	  */
	private function bsize($s){
		foreach (array('','K','M','G') as $i => $k) {
			if ($s < 1024) break;
			$s/=1024;
		}
		return round($s,2)." {$k}B";
	}
	
	/**
	  * Get Time Duration from Passed Unicode Time
	  *
	  * @author Peter Schmalfeldt
	  * @version 1.0
	  * @param int $ts Unicode Time
	  * @return string Time Duration
	  */
	private function duration($ts) {
		$time = time();
		$years = (int)((($time - $ts)/(7*86400))/52.177457);
		$rem = (int)(($time-$ts)-($years * 52.177457 * 7 * 86400));
		$weeks = (int)(($rem)/(7*86400));
		$days = (int)(($rem)/86400) - $weeks*7;
		$hours = (int)(($rem)/3600) - $days*24 - $weeks*7*24;
		$mins = (int)(($rem)/60) - $hours*60 - $days*24*60 - $weeks*7*24*60;
		$str = '';
		if($years==1) $str .= "$years year, ";
		if($years>1) $str .= "$years years, ";
		if($weeks==1) $str .= "$weeks week, ";
		if($weeks>1) $str .= "$weeks weeks, ";
		if($days==1) $str .= "$days day,";
		if($days>1) $str .= "$days days,";
		if($hours == 1) $str .= " $hours hour and";
		if($hours>1) $str .= " $hours hours and";
		if($mins == 1) $str .= " 1 minute";
		else $str .= " $mins minutes";
		return $str;
	}
}
?>