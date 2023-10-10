<h1 align="center">Welcome to portals 👋</h1>
<p>
  <a href="https://github.com/fsr5-fhaachen/portals/blob/main/LICENSE" target="_blank">
    <img alt="License: MIT" src="https://img.shields.io/github/license/fsr5-fhaachen/portals" />
  </a>
  <a href="https://twitter.com/fsr5_fhaachen" target="_blank">
    <img alt="Twitter: fsr5_fhaachen" src="https://img.shields.io/twitter/follow/fsr5_fhaachen.svg?style=social" />
  </a>
</p>

> Group allocation tool for the first week of the Department of Electrical Engineering and Information Technology at the FH Aachen - University of Applied Sciences.

## main

<a href="https://github.com/fsr5-fhaachen/portals/actions/workflows/ci.yml" target="_blank">
  <img alt="CI" src="https://github.com/fsr5-fhaachen/portals/actions/workflows/ci.yml/badge.svg" />
</a>

## dev

<a href="https://github.com/fsr5-fhaachen/portals/actions/workflows/ci.yml" target="_blank">
  <img alt="CI dev" src="https://github.com/fsr5-fhaachen/portals/actions/workflows/ci.yml/badge.svg?branch=dev" />
</a>

## Install

Clone the repository and install the dependencies:

```sh
git clone git@github.com:fsr5-fhaachen/portals.git
cd portals
npm install
composer install
```

Copy .env.example to .env and fill in the database credentials.

```sh
cp .env.example .env
```

Generate the application key:

```sh
php artisan key:generate
```

Run the migrations:

```sh
php artisan migrate
```

## Usage

### development

For development, you can use the built-in PHP server:

```sh
php artisan serve
```

and the vite dev server:

```sh
npm run dev
```

### devcontainer

If you want to use the provided devcontainer via laravel sail you need vscode and the devcontainer extension.

### type generation

To generate the typescript types for the frontend, run:

```sh
php artisan typescript:generate
```

### linting

You can lint the code with the following commands:

```sh
npm run lint
./vendor/bin/pint
```

and try to fix the errors with:

```sh
npm run lint:fix
./vendor/bin/pint
```

### testing

You can run the tests with:

```sh
vendor/bin/phpunit
```

### build

You can build the application with:

```sh
npm run build
```

### production

This project uses laravel octane with roadrunner as production server. You can install the server with:

This step will also ask you to download the roadrunner binary.

```sh
php artisan octane:install
```

You can run the production server with:
_Define the worker-count and max-requests to fit your needs._

```sh
php artisan octane:start --max-requests=512 --workers=4
```

### docker

If you want to use docker, use the following commands:

```sh
docker build -t ghcr.io/fsr5-fhaachen/portals:latest .
docker-compose up -d
docker exec -it portals-web touch database/seeders/tutors.csv
docker exec -it portals-web php artisan migrate:fresh --seed
```

### Kubernetes (Helm)

You can deploy the application to kubernetes using the helm chart.

See [fsr5-fhaachen/charts/portals](github.com/fsr5-fhaachen/charts/charts/portals/) for more information.

If you want information about creating the kubernetes cluster, see [deploy information](./deploy).

## Authors

👤 **Titus Kirch (main author)**

- Website: https://tkirch.dev/
- LinkedIn: [Titus Kirch](https://www.linkedin.com/in/tituskirch/)
- Twitter: [@TitusKirch](https://twitter.com/TitusKirch)
- Github: [@TitusKirch](https://github.com/TitusKirch)

👤 **Benedikt Haas (main author)**

- LinkedIn: [Benedikt Haas](https://www.linkedin.com/in/benedikt-haas-ab698924a/)
- Github: [@BenediktHaas96](https://github.com/BenediktHaas96)

👤 **Simon Ostendorf**

- LinkedIn: [Simon Ostendorf](https://www.linkedin.com/in/simonostendorf/)
- Github: [@simonostendorf](https://github.com/simonostendorf)

👤 **Patrik Schmolke**

- LinkedIn: [Patrik Schmolke](https://www.linkedin.com/in/patrik-schmolke-612962175/)
- Github: [@Rec0gnice](https://github.com/Rec0gnice)

Show here to see the full list of [contributors](https://github.com/fsr5-fhaachen/portals/graphs/contributors) who participated in this project.

## 🤝 Contributing

Contributions, issues and feature requests are welcome!<br />Feel free to check [issues page](https://github.com/fsr5-fhaachen/portals/issues).

## Show your support

Give a ⭐️ if this project helped you!

## 📝 License

Copyright © 2022 [fsr5-fhaachen](https://github.com/fsr5-fhaachen).<br />
This project is [MIT](https://github.com/fsr5-fhaachen/portals/blob/main/LICENSE) licensed.
