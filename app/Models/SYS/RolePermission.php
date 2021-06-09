<?php

namespace App\Models\SYS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class RolePermission extends Model
{
    use HasFactory;
    protected $table = 'sys_role_permissions';
    protected $fillable = ['id', 'role_id','function_id','action_id'];
    public $timestamps = false;

	public function action()
	{
		return $this->belongsTo('App\Models\SYS\Action');
	}
    /**
	 * Check Super Admin permissions
	 * NOTE: Must use try {...} catch {...}
	 *
	 * @return bool
	 */
	public static function checkSuperAdminPermissions()
	{
		try {
			$superAdminPermissions = array_merge((array)self::getSuperAdminPermissions(), (array)self::getStaffPermissions());
			if (!empty($superAdminPermissions)) {
				foreach ($superAdminPermissions as $superAdminPermission) {
					$permission = seft::action()->where('name', $superAdminPermission)->first();
					if (empty($permission)) {
						return false;
					}
				}
			} else {
				return false;
			}
		} catch (\Exception $e) {}
		
		return true;
    }
    /**
	 * Default Super Admin users permissions
	 *
	 * @return array
	 */
	public static function getSuperAdminPermissions()
	{
		$permissions = [
			'list-permission',
			'create-permission',
			'update-permission',
			'delete-permission',
			'list-role',
			'create-role',
			'update-role',
			'delete-role',
		];
		
		return $permissions;
	}
	
	/**
	 * Default Staff users permissions
	 *
	 * @return array
	 */
	public static function getStaffPermissions()
	{
		$permissions = [
			'laravel-filemanager','_ignition','api','routes','login','logout','dashboard','/','ajax'
		];
		
		return $permissions;
	}
    /**
	 * @return array
	 */
	public static function getRoutesPermissions()
	{
		$routeCollection = Route::getRoutes();
		
		// Controller's Action => Access
		$accessOfActionMethod = [
			'index'                    => 'list',
			'show'                     => 'show',
			'create'                   => 'create',
			'store'                    => 'create',
			'edit'                     => 'update',
			'update'                   => 'update',
			'destroy'                  => 'delete',
			'bulkDelete'               => 'delete',
			'dashboard'                => 'dashboard', // Dashboard
        ];
        
        $prefixIgnore = self::getStaffPermissions();
		$tab = $data = [];
		foreach ($routeCollection as $key => $value) {
			
			// Init.
			$data['filePath'] = null;
			$data['actionMethod'] = null;
			$data['methods'] = [];
			$data['permission'] = null;
			$data['prefix']=null;
			// Get & Clear the route prefix
            $routePrefix = $value->getPrefix();
			$routePrefix = trim($routePrefix, '/');
			$data['prefix'] = $routePrefix;
			// if ($routePrefix != 'admin') {
			// 	$routePrefix = head(explode('/', $routePrefix));
			// }
			//nếu có phía trước là tiền tố admin
            // if ($routePrefix == 'admin') 
            if(!in_array($routePrefix,$prefixIgnore) && !in_array($value->uri(),$prefixIgnore) )
            {
				$data['methods'] = $value->methods();
				
				$data['uri'] = $value->uri();
				$data['uri'] = preg_replace('#\{[^\}]+\}#', '*', $data['uri']);
				
				$controllerActionPath = $value->getActionName();
				
				try {
					$controllerNamespace = '\\' . preg_replace('#@.+#i', '', $controllerActionPath);
					$reflector = new \ReflectionClass($controllerNamespace);
					$data['filePath'] = $filePath = $reflector->getFileName();
				} catch (\Exception $e) {
					$data['filePath'] = $filePath = null;
				}
				
				$actionMethod = $value->getActionMethod();
				$access = (isset($accessOfActionMethod[$actionMethod])) ? $accessOfActionMethod[$actionMethod] : null;
				$data['actionMethod'] = $access??$actionMethod;
				 {
					$tmp = '';
					preg_match('#\\\([a-zA-Z0-9]+)Controller@#', $controllerActionPath, $tmp);
					$controllerSlug = (isset($tmp[1]) && !empty($tmp)) ? $tmp[1] : '';
					$controllerSlug = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $controllerSlug));
					$data['permission'] = (!empty($actionMethod)) ? ($access??$actionMethod). '-' . $controllerSlug : null;
				}
				
				if (empty($data['permission'])) {
					continue;
				}
				
				// if ($data['filePath']) {
				// 	unset($data['filePath']);
				// }
				// if ($data['actionMethod']) {
				// 	unset($data['actionMethod']);
				// }
				
				// Save It!
				$tab[$key] = $data;
			}
			
		}
		return $tab;
	}
}
