# opentelemetry-roadrunner

此範例展示如何透過 OpenTelemetry 來收集 Roadrunner (PHP 框架) 的 Metrics 與 Traces 並送到 Datadog 上。

✨ 以下範例中的部分設定在上一篇 [opentelemetry-ingress-nginx-controller](https://github.com/880831ian/opentelemetry-ingress-nginx-controller) 有提到，歡迎大家先去閱讀該文章 ❤️

<br>

## 檔案說明

- otel-collector.yaml：
  因為 Datadog 並沒有提供 Roadrunner integrations，沒辦法用 Datadog Agent 來收 Metrics，所以這邊我們使用 OpenTelemetry Collector 的 receivers.prometheus 來收集 Metrics，透過 php-svc 的 2122 來收集。

- ingress-nginx-values.yaml：
  這個檔案與上一篇範例相同 [opentelemetry-ingress-nginx-controller](https://github.com/880831ian/opentelemetry-ingress-nginx-controller)，所以這邊不多做說明。

- php.yaml：
  一個簡單的 PHP 整套服務 (Deployment、Service、Ingress)，要注意的是，這邊會接續上一篇 [opentelemetry-ingress-nginx-controller](https://github.com/880831ian/opentelemetry-ingress-nginx-controller) 的範例，所以 Ingress 設定還會有 nginx 的 host。

- Dockerfile、src/ 底下檔案：
  這邊我們參考 [Roadrunner 官網的 OpenTelemetry](https://roadrunner.dev/docs/lab-otel) 範例，做了一些小調整，下方會說明操作步驟。

<br>

## 執行步驟

1. 先 clone 這個 repo (建議拉 xD 這次有 Code 需要 build)
2. OpenTelemetry Collector 與 Ingress Nginx Controller 請參考上一篇 [opentelemetry-ingress-nginx-controller](https://github.com/880831ian/opentelemetry-ingress-nginx-controller)

3. 可以自行手動包 image，並更換到 php.yaml 中，也可以使用 [880831ian/rr-otel:demo](https://hub.docker.com/layers/880831ian/rr-otel/demo/images/sha256-5e4d7188b500bf3e66d19f31461bea12b1cbc7ca27ef727168e7198e0483d4f3?context=repo) image 直接來測試

4. 接著建立測試用 PHP 服務，執行以下指令：

   ```shell
   kubectl apply -f php.yaml
   ```

<br>

## 測試

當你執行完上面的步驟後，會有 OpenTelemetry Collector、Ingress Nginx Controller、PHP 等服務，如下：

<br>

![圖片](https://raw.githubusercontent.com/880831ian/opentelemetry-roadrunner/master/images/1.png)

<br>

我們試著打 `http://php.example.com/`，查看一下 Datadog 的 LOG，看看是否有收到 LOG，如下：

<br>

![圖片](https://raw.githubusercontent.com/880831ian/opentelemetry-roadrunner/master/images/2.png)

<br>

由於我們在 PHP 程式中，將 trace id 與 span id 都塞到 header 中，所以可以在 LOG 中看到 trace id 與 span id，以及可以透過 dd.trace_id 來連接 LOG 與 Trace：

<br>

![圖片](https://raw.githubusercontent.com/880831ian/opentelemetry-roadrunner/master/images/3.png)

<br>

當然也可以從 Datadog APM 的 Trace 來看到 LOG ( 這邊是指從 LOG 連結 Trace 的頁面有左側 View Trace Details 功能才正常顯示，因為 OpenTelemetry traceID 是 128 位無符號整數，但 Datadog 只能收 64 位無符號整數，所以目前用這樣的方式，沒有辦法從 APM 的 trace 連結到 LOG，可以參考 [Datadog 官網說明](https://docs.datadoghq.com/tracing/other_telemetry/connect_logs_and_traces/opentelemetry/?tab=php) )，如下：

<br>

![圖片](https://raw.githubusercontent.com/880831ian/opentelemetry-roadrunner/master/images/3.png)

<br>

![圖片](https://raw.githubusercontent.com/880831ian/opentelemetry-roadrunner/master/images/4.png)

<br>

最後，我們查看一下收集的 Roadrunner PHP Metrics，如下：

這邊說明一下，因為 Roadrunner 有提供 Prometheus Metrics，程式設定在 .rr.yaml 檔案中，接著我們在 otel-collector.yaml 中設定 receivers.prometheus 來收集 Metrics，要記得再 php.yaml 中開啟對應的 2112 Port。

<br>

![圖片](https://raw.githubusercontent.com/880831ian/opentelemetry-roadrunner/master/images/5.png)

<br>

## 結論

前一個只有 Ingress Nginx Controller，看不出整個 Trace 的流程，這邊我們透過 Roadrunner PHP 來做一個簡單的服務，就可以看到整個 Trace 的流程，也可以透過 Datadog LOG 頁面來看到 LOG 與 Trace 的關聯，並用 receivers.prometheus 來收集 Roadrunner PHP Metrics。

<br>

## 參考

[Roadrunner Observability — OpenTelemetry](https://roadrunner.dev/docs/lab-otel)

[otel-roadrunner-example](https://github.com/opentelemetry-php/otel-roadrunner-example/tree/main)
