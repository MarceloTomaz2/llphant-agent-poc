<?php

namespace SearchAgent\AI\Tools;

use GuzzleHttp\Client;

class SearchWebTool
{
    /**
     * Pesquisa informações na internet usando SearXNG
     */
    public function search(string $query): string
    {
        echo "pesquisando na web: " . $query . PHP_EOL;

        $client = new Client();
        $options = [
            'multipart' => [
                [
                    'name' => 'q',
                    'contents' => $query
                ],
                [
                    'name' => 'format',
                    'contents' => 'json'
                ]
            ]
        ];

        $searchUrl = $_ENV['SEARXNG_URL'] ?? $_SERVER['SEARXNG_URL'] ?? 'http://localhost/search';
        $request = new \GuzzleHttp\Psr7\Request('POST', $searchUrl);
        $response = $client->sendAsync($request, $options)->wait();

        $data = json_decode(
            $response->getBody()->getContents(),
            true
        );

        $results = [];

        foreach (($data['results'] ?? []) as $item) {

            $results[] = [
                'url' => $item['url'] ?? '',
                'snippet' => $item['content'] ?? ''
            ];
        }

        $rtn = json_encode(
            array_slice($results, 0, 3),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        return $rtn;
    }
}