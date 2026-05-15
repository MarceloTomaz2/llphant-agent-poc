<?php

namespace SearchAgent\AI\Tools;

use GuzzleHttp\Client;
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;

class ReadUrlTool
{
    public function read(string $url): string
    {
        echo "Lendo conteudo da url: " . $url . PHP_EOL;
        try {

            $client = new Client();
            $options = [
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0'
                ]
            ];

            $request = new \GuzzleHttp\Psr7\Request('GET', $url);
            $response = $client->sendAsync($request, $options)->wait();

            $html = $response->getBody()->getContents();

            $config = new Configuration();

            $readability = new Readability($config);

            $readability->parse($html);

            $content = strip_tags(
                $readability->getContent()
            );

            $content = html_entity_decode($content);

            $content = preg_replace('/\s+/', ' ', $content);

            return json_encode([
                'title' => $readability->getTitle(),
                'content' => mb_substr($content, 0, 12000)
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            return json_encode([
                'error' => 'Falha ao ler URL: ' . $e->getMessage()
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }
}