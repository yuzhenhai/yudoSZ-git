<?php
/**
 * @Author: GouChengZu
 * @Date: 2024-10-23 11:28
 */

namespace app\common;

class Util
{
    /**
     * 将 PDF 转换为图像 服务器需要安装ghostscript软件
     *
     * @param string $pdfPath PDF 文件路径
     * @param string $outputPath 输出图像路径
     * @param int $resolution 分辨率
     * @return bool
     */
    public static function pdfToImage($pdfPath, $outputPath = null, $resolution = 300)
    {
        // 提取 PDF 文件名（不带扩展名）
        $pdfName = pathinfo($pdfPath, PATHINFO_FILENAME);
        // 如果没有传入输出路径，使用 PDF 文件的目录
        if ($outputPath === null) {
            $outputPath = dirname($pdfPath); // 使用 PDF 文件的目录
        }
        // 设置输出图片的路径（包含文件名）
        $outputImagePath = rtrim($outputPath, '/') . "/$pdfName.jpg"; // 确保路径结尾有斜杠
        // 确保输出目录存在，并设置权限为 0755
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true); // 创建目录，递归
        }
        chmod($outputPath, 0755); // 确保目录权限为 0755
        // 设置要执行的命令
        $command = "gs -sstdout=%stderr -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT " .
            "-dMaxBitmap=500000000 -r$resolution " .
            "-dAlignToPixels=0 -dGridFitTT=2 " .
            "-sDEVICE=jpeg -dTextAlphaBits=4 " .
            "-dGraphicsAlphaBits=4 -dPrinted=false " .
            "-sOutputFile=$outputImagePath $pdfPath"; // 使用完整的输出路径
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);

        return $return_var === 0;
    }

    /**
     * 使用 cURL 执行 POST 请求
     *
     * @param string $url 请求的 URL
     * @param array $data 发送的数据
     * @param array $headers 可选的请求头
     * @return mixed 返回响应内容，失败时返回 false
     */
    public static function postCurl($url, $data = [], $headers = [])
    {
        // 初始化 cURL
        $ch = curl_init($url);
        // 设置为 POST 请求
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // 设置返回响应内容，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求头
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        // 执行请求并获取响应
        $response = curl_exec($ch);
        // 检查是否发生错误
        if (curl_errno($ch)) {
            // 处理错误
            $response = false;
        }
        // 关闭 cURL 句柄
        curl_close($ch);
        return $response;
    }

    /**
     * 使用 cURL 执行 GET 请求
     *
     * @param string $url 请求的 URL
     * @param array $params 可选的查询参数
     * @param array $headers 可选的请求头
     * @return mixed 返回响应内容，失败时返回 false
     */
    public static function getCurl($url, $params = [], $headers = [])
    {
        // 如果有查询参数，将其附加到 URL
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        // 初始化 cURL
        $ch = curl_init($url);
        // 设置为 GET 请求
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        // 设置返回响应内容，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 设置请求头
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        // 执行请求并获取响应
        $response = curl_exec($ch);
        // 检查是否发生错误
        if (curl_errno($ch)) {
            // 处理错误
            $response = false;
        }
        // 关闭 cURL 句柄
        curl_close($ch);
        return $response;
    }


}
