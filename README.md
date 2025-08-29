# 勤怠アプリ

## 環境構築

### Dockerビルド
1.  git clone [https://github.com/aya0605/mockcase-furima.git](https://github.com/aya0605/mockcase-furima.git)
2.  cd coachtech/laravel/mockcase-furima
3.  DockerDesktopアプリを立ち上げる
4.  docker-compose up -d --build
    ```yaml
    mysql:
        platform: linux/x86_64(この文追加)
        image: mysql:8.0.26
        environment:
    ```

### Laravel環境構築
1.  docker-compose exec php bash
2.  composer install
3. 「.env.example」ファイルを 「.env」ファイルに命名を変更。または、新しく.envファイルを作成
4.  .envに以下の環境変数を追加
    ```dotenv
    DB_CONNECTION=mysql  
    DB_HOST=mysql  
    DB_PORT=3306  
    DB_DATABASE=laravel_db  
    DB_USERNAME=laravel_user  
    DB_PASSWORD=laravel_pass  
    ```


5.  アプリケーションキーの作成
    ```bash
    php artisan key:generate
    ```

6.  マイグレーションの実行
    ```bash
    php artisan migrate
    ```

7.  シーディングの実行
    ```bash
    php artisan db:seed
    ```

8.  シンボリックリンク作成
    ```bash
    php artisan storage:link
    ```

## 使用技術(実行環境)

* PHP8.3.0
* Laravel8.83.27
* MySQL8.0.26

## URL
* **開発環境**： (http://localhost/)
* **phpMyAdmin**:  (http://localhost:8080/)



