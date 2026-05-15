<?php

namespace SearchAgent\AI\Tools;

class ReadSkillTool
{
    /**
     * Lê o conteúdo de um arquivo skill.md de uma skill específica.
     * 
     * @param string $folderName O nome da pasta da skill dentro do diretório .SKILLS.
     * @return string O conteúdo do arquivo skill.md ou uma mensagem de erro.
     */
    public function readSkill(string $folderName): string
    {
        echo "lendo skill: " . $folderName . PHP_EOL;
        $rootPath = dirname(__DIR__, 3);
        $filePath = $rootPath . DIRECTORY_SEPARATOR . '.SKILLS' . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . 'skill.md';

        if (file_exists($filePath) && is_readable($filePath)) {
            return file_get_contents($filePath);
        }

        return "Erro: O arquivo skill.md não foi encontrado na pasta '{$folderName}'.";
    }

    /**
     * Lista todos os diretórios dentro de .SKILLS, extrai o name e description
     * de cada skill.md e retorna uma string concatenada.
     * 
     * @return string O conteúdo formatado com name e description de cada skill.
     */
    public static function listSkills(): string
    {
        $rootPath = dirname(__DIR__, 3);
        $skillsDir = $rootPath . DIRECTORY_SEPARATOR . '.SKILLS';

        if (!is_dir($skillsDir)) {
            return "Erro: Diretório .SKILLS não encontrado.";
        }

        $items = scandir($skillsDir);
        $result = "";
        $count = 1;

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $skillsDir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                $skillFilePath = $itemPath . DIRECTORY_SEPARATOR . 'skill.md';

                if (file_exists($skillFilePath) && is_readable($skillFilePath)) {
                    $content = file_get_contents($skillFilePath);

                    $name = $item; // Valor padrão
                    $description = "Sem descrição";

                    if (preg_match('/^name:\s*(.+)$/m', $content, $matchesName)) {
                        $name = trim($matchesName[1]);
                    }

                    if (preg_match('/^description:\s*(.+)$/m', $content, $matchesDesc)) {
                        $description = trim($matchesDesc[1]);
                        // Remove possíveis aspas na descrição
                        $description = trim($description, "\"'");
                    }

                    $result .= "{$count}. {$name}\nDescrição: {$description}\n";
                    $count++;
                }
            }
        }

        if (empty($result)) {
            return "Nenhuma skill encontrada no diretório .SKILLS.";
        }

        return rtrim($result);
    }
}
