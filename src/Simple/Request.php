<?php

namespace Simple;
use Simple\Database\Connection;
use Symfony\Component\HttpFoundation\Request as RQ;

class Request extends RQ
{
    use Connection;

    public function bootstrap()
    {
        $this->connect();
        if(class_exists(\App\Providers\EventServiceProvider::class)) {
            $events = new \App\Providers\EventServiceProvider;
            $events->boot();
        }
    }

    /**
     * Return POST values
     * @param null $key
     * @return array|bool|float|int|string|null
     */
    public function post($key = null)
    {
        if($key!==null) return $this->request->get($key);
        return $this->request->all();
    }

    /**
     * Redirects to given URL
     * @param string $url - Redirect to given URL
     * @param array $param - GET parameters to be pass
     */
    public static function redirect(string $url, $param = [])
    {
        if (preg_match('#^(https?:)?//#i', $url)) {
            throw new \RuntimeException('Redirect to external URLs is not allowed.');
        }
        $params="?";
        foreach ($param as $key => $value) {
            $params.=$key.'='.$value.'&';
        }
        $url .= substr($params, 0 , -1);
        header('location:'.\Simple\Config::get('app.baseurl', '').$url,true,303);
        exit();
    }

    /**
     * Get the variables passed to route as parameters eg: id, name, product_id
     * @param string|null $key variable to route
     * @return array|string
     */
    public static function route(string $key=null)
    {
        $params = \Simple\Routing\Router::getParams();
        return $params[$key] ?? $params;
    }
}
