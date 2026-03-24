<?php
/**
 * AI API 客户端 - 统一处理所有AI接口调用
 * 消除原代码中三处重复的callAPI逻辑
 */

class AIClient {
    const REQUEST_TIMEOUT = 60;

    /**
     * 调用指定来源的AI API
     */
    public static function generate(
        string $apiSource,
        array $messages,
        float $temperature = 0.7,
        int $maxTokens = 2000
    ): string {
        $apiSource = strtolower($apiSource);

        if ($apiSource === 'deepseek') {
            return self::callDeepSeek($messages, $temperature, $maxTokens);
        } elseif ($apiSource === 'ailion') {
            return self::callAilion($messages, $temperature, $maxTokens);
        } else {
            throw new Exception("不支持的AI来源: $apiSource");
        }
    }

    /**
     * 调用DeepSeek API
     */
    public static function callDeepSeek(
        array $messages,
        float $temperature = 0.7,
        int $maxTokens = 2000
    ): string {
        $config = Config::getAIConfig('DEEPSEEK');

        if (!$config['api_key']) {
            throw new Exception('DeepSeek API密钥未配置');
        }

        $payload = [
            'model' => $config['model'] ?: 'deepseek-chat',
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        return self::sendRequest($config['api_url'] . '/v1/chat/completions', $config['api_key'], $payload);
    }

    /**
     * 调用Ailion API
     */
    public static function callAilion(
        array $messages,
        float $temperature = 0.7,
        int $maxTokens = 2000
    ): string {
        $config = Config::getAIConfig('AILION');

        if (!$config['api_key']) {
            throw new Exception('Ailion API密钥未配置');
        }

        $payload = [
            'model' => $config['model'] ?: 'gpt-3.5-turbo',
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ];

        return self::sendRequest($config['api_url'] . '/v1/chat/completions', $config['api_key'], $payload);
    }

    /**
     * 发送HTTP请求到AI API
     */
    private static function sendRequest(string $url, string $apiKey, array $payload): string {
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);

        // 根据环境决定是否验证SSL
        if (Config::get('APP_ENV') === 'production') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('API请求失败: ' . $error);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("API返回错误码: $httpCode，响应: $response");
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('API响应格式错误: ' . json_last_error_msg());
        }

        // 标准的OpenAI兼容API格式
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }

        // 备用格式
        if (isset($responseData['output'])) {
            return $responseData['output'];
        }

        if (isset($responseData['text'])) {
            return $responseData['text'];
        }

        throw new Exception('无法从API响应中提取文本内容');
    }

    /**
     * 生成文章
     * 这是最常见的用法，提供一个便捷方法
     */
    public static function generateArticle(
        string $apiSource,
        string $title,
        string $requirements,
        int $wordLimit,
        string $style
    ): string {
        $stylePrompt = self::getStylePrompt($style);

        $messages = [
            [
                'role' => 'system',
                'content' => "你是一位专业的内容创作者，擅长根据需求撰写高质量文章。请用{$stylePrompt}风格写作。"
            ],
            [
                'role' => 'user',
                'content' => "请根据以下要求撰写一篇文章：\n标题：{$title}\n内容要求：{$requirements}\n字数限制：大约{$wordLimit}字。"
            ]
        ];

        $maxTokens = min(max($wordLimit * 2, 1000), 4000);

        return self::generate($apiSource, $messages, 0.7, $maxTokens);
    }

    /**
     * 润色内容需求
     */
    public static function polishRequirements(string $requirements, string $apiSource = 'deepseek'): string {
        $messages = [
            [
                'role' => 'system',
                'content' => '你是一位专业的文案编辑。用户会提供一个粗糙的文章需求描述，请你用专业、清晰、结构化的方式进行优化和润色。'
            ],
            [
                'role' => 'user',
                'content' => "请对以下文章需求进行润色和优化：\n$requirements"
            ]
        ];

        return self::generate($apiSource, $messages, 0.7, 1000);
    }

    /**
     * 生成话题建议
     */
    public static function generateTopics(
        string $industry,
        int $count,
        string $apiSource = 'deepseek'
    ): array {
        if ($count < 5 || $count > 10) {
            $count = 5;
        }

        $messages = [
            [
                'role' => 'system',
                'content' => "你是一位创意内容策划师。给定一个行业或领域，你能快速生成该领域的热门和有趣的文章话题。请直接返回话题列表，每行一个，不要包含序号、符号或任何前缀。"
            ],
            [
                'role' => 'user',
                'content' => "请为'{$industry}'行业生成{$count}个有吸引力的文章话题。"
            ]
        ];

        $response = self::generate($apiSource, $messages, 0.8, 500);

        // 清理响应：移除Markdown格式、序号等
        $lines = array_filter(
            array_map('trim', explode("\n", $response)),
            function ($line) {
                return !empty($line) && strlen($line) > 2;
            }
        );

        // 进一步清理每一行
        $topics = array_map(function ($line) {
            // 移除序号
            $line = preg_replace('/^\d+[\.\)，、]\s*/', '', $line);
            // 移除Markdown格式
            $line = preg_replace('/[#*`【】]/', '', $line);
            return trim($line);
        }, $lines);

        return array_slice(array_values($topics), 0, $count);
    }

    /**
     * 获取风格对应的提示词
     */
    private static function getStylePrompt(string $style): string {
        return match($style) {
            'formal' => '正式、严谨、专业',
            'casual' => '轻松、活泼、亲切',
            'tech' => '科技感、未来感、专业',
            'professional' => '行业专业、精确、权威',
            'creative' => '创意、新颖、独特',
            default => '清晰、简洁、易懂',
        };
    }
}
