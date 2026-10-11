<?php
// Express-style router: mounts with router-level middleware, ":param" segments,
// case-insensitive matching and an optional trailing slash.

class Router
{
    private array $mounts = [];

    // $middleware runs for every request under $prefix (like router.use(...)), even when no route matches
    public function mount(string $prefix, array $middleware, array $routes): void
    {
        $this->mounts[] = [rtrim($prefix, '/'), $middleware, $routes];
    }

    public static function compile(string $pattern): string
    {
        $segments = [];
        foreach (explode('/', trim($pattern, '/')) as $seg) {
            if ($seg === '') continue;
            $segments[] = $seg[0] === ':'
                ? '(?P<' . substr($seg, 1) . '>[^/]+)'
                : preg_quote($seg, '#');
        }
        return '#^/' . implode('/', $segments) . '/?$#i';
    }

    // Returns true when a route matched (its handlers send the response)
    public function dispatch(Req $R, string $path): bool
    {
        foreach ($this->mounts as [$prefix, $middleware, $routes]) {
            if ($prefix !== '') {
                if (strcasecmp($path, $prefix) !== 0 && stripos($path, $prefix . '/') !== 0) continue;
                $sub = substr($path, strlen($prefix));
                if ($sub === '') $sub = '/';
            } else {
                $sub = $path;
            }

            foreach ($middleware as $mw) $mw($R);

            foreach ($routes as [$method, $pattern, $handlers]) {
                if ($method !== $R->method && !($method === 'GET' && $R->method === 'HEAD')) continue;
                if (!preg_match(self::compile($pattern), $sub, $m)) continue;
                $R->params = [];
                foreach ($m as $k => $v) {
                    if (is_string($k)) $R->params[$k] = rawurldecode($v);
                }
                foreach ($handlers as $h) $h($R);
                return true;
            }
        }
        return false;
    }
}
