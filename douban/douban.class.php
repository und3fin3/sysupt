<?php

class Douban
{
    private $id;
    private $type;

    private $apikeys = array(
        "02646d3fb69a52ff072d47bf23cef8fd",
        "0b2bdeda43b5688921839c8ecb20399b",
        "0dad551ec0f84ed02907ff5c42e8ec70",
        "0df993c66c0c636e29ecbb5344252a4a"
    );
    private $apikey;
    private $url = "http://api.douban.com/v2/movie";
    private $cache_dir = "./douban/cache";
    private $cache_ttl = 60 * 60 * 24 * 14; // 14 days

    private $data;

    public function __construct($id, $type = 'imdb')
    {
        $this->id = $id;
        $this->type = $type;
        $this->apikey = $this->apikeys[array_rand($this->apikeys)];
        if ($type == 'imdb') {
            $this->url .= "/imdb/tt" . $id . "?apikey=" . $this->apikey;
            $this->cache_dir .= "/imdb/";
        } else {
            $this->url .= "/subject/" . $id . "?apikey=" . $this->apikey;
            $this->cache_dir .= "/douban/";
        }
    }

    public function get_movie()
    {
        $cache_file = $this->cache_dir . $this->id . ".json";
        if (!file_exists($cache_file) || time() - filemtime($cache_file) > $this->cache_ttl) {
            $this->data = json_decode(file_get_contents($this->url), TRUE);
            $fp = fopen($cache_file, "w");
            fwrite($fp, serialize($this->data));
            fclose($fp);
        } else {
            $this->data = unserialize(file_get_contents($cache_file));
            if (!$this->data) {
                $this->clear_cache();
                $this->data = json_decode(file_get_contents($this->url), TRUE);
                $fp = fopen($cache_file, "w");
                fwrite($fp, serialize($this->data));
                fclose($fp);
            }
        }
        return $this->data;
    }

    public function clear_cache()
    {
        $cache_file = $this->cache_dir . $this->id . ".json";
        unlink($cache_file);
    }

    public function get_data($name)
    {
        switch ($name) {
            case 'country':
                if ($this->type == 'imdb') {
                    return $this->data['attrs']['country'];
                } else {
                    return $this->data['countries'];
                }
            case 'director':
                if ($this->type == 'imdb') {
                    return $this->data['author'];
                } else {
                    return $this->data['directors'];
                }
            case 'creator':
                if ($this->type == 'imdb') {
                    return $this->data['attrs']['writer'];
                } else {
                    $writers = array();
                    foreach ($this->data['writers'] as $writer) {
                        $writers[] = $writer['name'];
                    }
                    return $writers;
                }
            case 'writing':
                if ($this->type == 'imdb') {
                    return $this->data['attrs']['writer'];
                } else {
                    $writers = array();
                    foreach ($this->data['writers'] as $writer) {
                        $writers[] = $writer['name'];
                    }
                    return $writers;
                }
            case 'cast':
                if ($this->type == 'imdb') {
                    return $this->data['attrs']['cast'];
                } else {
                    $casts = array();
                    foreach ($this->data['casts'] as $cast) {
                        $casts[] = $cast['name'];
                    }
                    return $casts;
                }
            case 'plotoutline':
                return $this->data['summary'];
            case 'genres':
                if ($this->type == 'imdb') {
                    return $this->data['attrs']['movie_type'];
                } else {
                    return $this->data['genres'];
                }
            case 'title':
                if ($this->type == 'imdb') {
                    return $this->data['title'];
                } else {
                    return $this->data['original_title'];
                }
            case 'transname':
                if ($this->type == 'imdb') {
                    return $this->data['alt_title'];
                } else {
                    return $this->data['title'];
                }
            case 'runtime_all':
                if ($this->type == 'imdb') {
                    return implode($this->data['attrs']['movie_duration'], ', ');
                } else {
                    return implode($this->data['durations'], ', ');
                }

            case 'year':
                if ($this->type == 'imdb') {
                    return implode($this->data['attrs']['year'], ', ');
                } else {
                    return implode($this->data['pubdates'], ', ');
                }
            case 'votes':
                if ($this->type == 'imdb') {
                    return $this->data['rating']['numRaters'];
                } else {
                    return $this->data['rating']['ratings_count'];
                }
            case 'rating':
                return $this->data['rating']['average'];
            case 'language':
                if ($this->type == 'imdb') {
                    return implode($this->data['attrs']['language'], ', ');
                } else {
                    return implode($this->data['languages'], ', ');
                }
            case 'tagline':
                $tags = array();
                foreach ($this->data['tags'] as $tag) {
                    $tags[] = $tag['name'];
                }
                return implode($tags, ', ');
            case 'photo_localurl':
                if ($this->type == 'imdb') {
                    return $this->data['image'];
                } else {
                    return $this->data['images']['large'];
                }
            case 'douban_id':
                if ($this->type == 'imdb') {
                    preg_match("/(\d+)/i", $this->data['id'], $matches);
                    return $matches[1];
                } else {
                    return $this->data['id'];
                }
            default:
                return "";
        }
    }
}