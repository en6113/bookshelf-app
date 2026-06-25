# Bookshelf 書籍レビューアプリ

書籍レビュー・読書記録の機能を実装したLaravelプロジェクトです。
誰でも書籍一覧、書籍詳細（レビュー含む）、ランキングの閲覧が可能で、一般ユーザーが書籍やレビューの登録及び読書記録ができます。

## 作成者

en6113

## 使用技術

### 🛠️ バックエンド
- PHP 8.2.x
- Laravel 10.x
  - Laravel Fortify (認証機能)
  - Laravel Sanctum (APIトークン認証)
  - Notification (通知機能)

### 💻 フロントエンド
- Blade(テンプレートエンジン)
- Tailwind CSS 3.4
- Vite（ビルドツール）

### 🗄️ データベース
- MySQL 8.0

### 🔌外部API連携
- Google Books API(ISBN検索)

### 🐳 インフラ / 開発環境
- Docker / Docker Compose
- Nginx (Webサーバー)
- phpMyAdmin (データベース管理ツール)


## ER図

```mermaid
erDiagram
    users {
        bigint_unsigned id PK
        varchar_255 name
        varchar_255 email UK
        timestamp email_verified_at
        varchar_255 password
        varchar_100 remember_token
        timestamp created_at
        timestamp updated_at
    }

    reviews {
        bigint_unsigned id PK
        bigint_unsigned user_id FK "UNIQUE(user_id, book_id)"
        bigint_unsigned book_id FK
        tinyInt_unsigned rating
        text comment
        timestamp created_at
        timestamp updated_at
    }

    books {
        bigint_unsigned id PK
        varchar_255 title
        varchar_100 author
        varchar_13 isbn UK
        date published_date
        text description
        varchar_255 image_url
        timestamp created_at
        timestamp updated_at
    }

    genres {
        bigint_unsigned id PK
        varchar_50 genre UK
        timestamp created_at
        timestamp updated_at
    }

    book_genre {
        bigint_unsigned id PK
        bigint_unsigned book_id FK "UNIQUE(book_id, genre_id)"
        bigint_unsigned genre_id FK
        timestamp created_at
        timestamp updated_at
    }

    favorites {
        bigint_unsigned id PK
        bigint_unsigned user_id FK "UNIQUE(user_id, book_id)"
        bigint_unsigned book_id FK
        timestamp created_at
        timestamp updated_at
    }

    likes {
        bigint_unsigned id PK
        bigint_unsigned user_id FK "UNIQUE(user_id, review_id)"
        bigint_unsigned review_id FK
        timestamp created_at
        timestamp updated_at
    }

    users ||--o{ reviews : "has many"
    books ||--o{ reviews : "has many"
    books ||--o{ book_genre : "has many"
    genres ||--o{ book_genre : "has many"
    users ||--o{ favorites : "has many"
    books ||--o{ favorites : "has many"
    users ||--o{ likes : "has many"
    books ||--o{ likes : "has many"
```

## 開発環境URL

http://localhost

## 動作環境

- Docker
- Docker Compose

※ Windowsの場合はWSL2の利用を推奨します。

## 環境構築手順

1. **リポジトリのクローン**

    ```bash
    git clone https://github.com/en6113/bookshelf-app.git
    ```

2. **.envファイルの準備**

    `.env.example` をコピーして `.env` を作成します。

    ```bash
    cp .env.example .env
    ```

    `.env` ファイル内の以下のDB接続情報が以下と一致していることを確認してください。

    ```ini
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=laravel
    DB_USERNAME=sail
    DB_PASSWORD=password
    ```

3. **Composer依存パッケージのインストール**

    プロジェクトの初回セットアップ時は、`vendor` ディレクトリが存在しないため `sail` コマンドを使用できません。
    以下のDockerコマンドを実行して、コンテナ内で `composer install` を実行します。

    ```bash
    docker run --rm 
    -u "$(id -u):$(id -g)" 
    -v "$(pwd):/var/www/html" 
    -w /var/www/html 
    -e COMPOSER_CACHE_DIR=/tmp/composer_cache laravelsail/php82-composer:latest 
    composer install
    ```

4. **Laravel Sailの起動**

    以下のコマンドでDockerコンテナを起動します。

    ```bash
    ./vendor/bin/sail up -d
    ```

    > **エイリアスの設定（推奨）**
    >
    > 毎回 `./vendor/bin/sail` と入力するのは手間なので、エイリアスを設定すると便利です。
    >
    > ```bash
    > alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
    > ```

5. **アプリケーションキーの生成**

    ```bash
    sail artisan key:generate
    ```

6. **データベースのマイグレーションと初期データ投入**

    以下のコマンドでテーブルを作成し、ダミーデータを投入します。

    ```bash
    sail artisan migrate:fresh --seed
    ```
    このコマンドの入力後、コンテナ内にデータが残っており、エラーが生じているケースなどがあります。
    その場合は、以下のコマンドを順に実行して各コンテナを再起動して下さい。
    ```Bash
    sail down -v
    sail up -d　//コマンド実行後にSQLコンテナが立ち上がるまで時間がかかります。30秒ほどお待ちください。
    sail artisan migrate:fresh --seed
    ```
    

7. **フロントエンドの準備**

    ```bash
    sail npm install
    sail npm install alpinejs
    sail npm run dev
    ```

    `npm run dev` は開発中は起動したままにしてください。

8. **アプリケーションへのアクセス**

    ブラウザで [http://localhost](http://localhost) にアクセスします。

## テスト実行

```bash
sail artisan test
```

カバレッジ付きで実行する場合:

```bash
sail artisan test --coverage
```

## 機能一覧

### 👤一般ユーザー向け機能
* **アカウント管理**
- ユーザー登録 / ログイン / ログアウト(Laravel Fortify)
* **書籍閲覧・検索（ログイン不要）**
- 書籍一覧表示（キーワード検索・ジャンル検索・並び順変更）
- 書籍詳細表示（レビュー付）
- ランキング表示（レビュー高評価順）
- 書籍登録（手動登録、GoogleBooksAPI経由の自動取得）/ 編集 / 削除
* **ジャンル管理**
- ジャンル一覧表示 / 新規追加 / 更新 / 削除
* **コミュニティ機能**
- レビューの投稿（投稿・編集・削除）
- レビューへの「いいね」機能（登録・解除）
* **マイページ（読書管理）**
- お気に入り表示（一覧表示・登録・解除）
- マイレポート表示（レビュー数、読了冊数、平均評価、評価分布、高評価書籍、ジャンル別評価傾向）
- 読書計画表示（一覧表示・作成・編集・削除・通知機能）

### 🔌外部公開用API（外部アプリケーション向け）
* **書籍データ連携API(LaravelSanctum認証必須)**
- 書籍一覧取得(GET) / 書籍詳細取得(GET)
- 書籍登録(POST) / 書籍更新(PUT) / 書籍削除(DELETE)


## APIエンドポイント一覧

認証不要の公開APIです。全エンドポイントは `/api/v1` プレフィックス配下に定義されています。

| HTTPメソッド | URI | 概要 |
|---|---|---|
| GET | /api/v1/books | 書籍一覧（検索・ページネーション付き） |
| GET | /api/v1/books/{book} | 書籍詳細（ジャンル含む） |
| POST | /api/v1/books | 書籍新規登録 |
| PUT | /api/v1/books/{book} | 書籍更新 |
| DELETE | /api/v1/books/{book} | 書籍削除 |