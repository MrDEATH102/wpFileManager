<?php
// Simple stubs for WordPress functions and environment
class WP_Error {}
function sanitize_text_field($v){return $v;}
function esc_url_raw($v){return $v;}
function is_wp_error($v){return $v instanceof WP_Error;}
function wp_upload_dir(){return ['basedir'=>sys_get_temp_dir().'/uploads','baseurl'=>'https://example.com/uploads'];}
function media_handle_upload($file,$id){return 1;}
function get_post($id){return (object)['post_title'=>'demo'];}
function get_attached_file($id){$dir=wp_upload_dir()['basedir'];return $dir.'/file.txt';}
function wp_get_attachment_url($id){return wp_upload_dir()['baseurl'].'/file.txt';}
function get_post_mime_type($id){return 'text/plain';}
function wp_create_nonce($a){return 'nonce';}
function add_query_arg($a,$u){return $u;}
function home_url(){return 'https://example.com';}
function wp_verify_nonce($n,$a){return true;}
function wp_parse_url($url,$component){return parse_url($url,$component);}

class WPDB_Stub {
    public $prefix='';
    public $insert_data=[];
    public $insert_id=1;
    public $deleted_where=[];
    public function insert($table,$data,$format){$this->insert_data=$data;return true;}
    public function delete($table,$where,$format){$this->deleted_where=$where;return true;}
    public function prepare($q,$id){return $q;}
    public function get_row($q){return (object)['id'=>1,'file_path'=>'/file.txt','external_url'=>null];}
    public function update(){return true;}
    public function get_results($q){return [];} 
}

$dir = wp_upload_dir()['basedir'];
@mkdir($dir,0777,true);
file_put_contents($dir.'/file.txt','test');

global $wpdb; 
$wpdb = new WPDB_Stub();
require_once __DIR__.'/../includes/class-fam-file.php';

$f = new FAM_File();
$f->create(1,['name'=>'file.txt','type'=>'text/plain']);
assert($wpdb->insert_data['file_path']==='/file.txt');

$f->delete(1);
assert(!file_exists($dir.'/file.txt'));

echo "Tests passed\n";
?>
