<h1 align="center">Welcome to groups 👋</h1>
<p>
  <a href="https://github.com/fsr5-fhaachen/groups/blob/main/LICENSE" target="_blank">
    <img alt="License: MIT" src="https://img.shields.io/github/license/fsr5-fhaachen/groups" />
  </a>
  <a href="https://twitter.com/fsr5_fhaachen" target="_blank">
    <img alt="Twitter: fsr5_fhaachen" src="https://img.shields.io/twitter/follow/fsr5_fhaachen.svg?style=social" />
  </a>
</p>

> Group allocation tool for the first week of the Department of Electrical Engineering and Information Technology at the FH Aachen - University of Applied Sciences.

## main
<a href="https://github.com/fsr5-fhaachen/groups/actions/workflows/ci.yml" target="_blank">
  <img alt="CI" src="https://github.com/fsr5-fhaachen/groups/actions/workflows/ci.yml/badge.svg" />
</a>

## dev
<a href="https://github.com/fsr5-fhaachen/groups/actions/workflows/ci.yml" target="_blank">
  <img alt="CI dev" src="https://github.com/fsr5-fhaachen/groups/actions/workflows/ci.yml/badge.svg?branch=dev" />
</a>

## Install

Clone the repository and install the dependencies:

```sh
git clone git@github.com:fsr5-fhaachen/groups.git
cd groups
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

### linting

You can lint the code with the following commands:

```sh
npm run lint
php artisan fixer:fix --dry-run -vvv
```

and try to fix the errors with:

```sh
npm run lint:fix
php artisan fixer:fix -vvv
```

### testing

You can run the tests with:

```sh
vendor/bin/phpunit
```


## Authors

👤 **Titus Kirch (main author)**

* Website: https://tkirch.dev/
* LinkedIn: [Titus Kirch](https://www.linkedin.com/in/tituskirch/)
* Twitter: [@TitusKirch](https://twitter.com/TitusKirch)
* Github: [@TitusKirch](https://github.com/TitusKirch)

👤 **Benedikt Haas (main author)**

* LinkedIn: [Benedikt Haas](https://www.linkedin.com/in/benedikt-haas-ab698924a/)
* Github: [@BenediktHaas96](https://github.com/BenediktHaas96)

👤 **Simon Ostendorf**

* LinkedIn: [Titus Kirch](https://www.linkedin.com/in/simonostendorf/)
* Github: [@simonostendorf](https://github.com/simonostendorf)

👤 **Patrik Schmolke**

* LinkedIn: [Patrik Schmolke](https://www.linkedin.com/in/patrik-schmolke-612962175/)
* Github: [@Rec0gnice](https://github.com/Rec0gnice)

Show here to see the full list of [contributors](https://github.com/fsr5-fhaachen/groups/graphs/contributors) who participated in this project.

## 🤝 Contributing

Contributions, issues and feature requests are welcome!<br />Feel free to check [issues page](https://github.com/fsr5-fhaachen/groups/issues). 

## Show your support

Give a ⭐️ if this project helped you!

## 📝 License

Copyright © 2022 [fsr5-fhaachen](https://github.com/fsr5-fhaachen).<br />
This project is [MIT](https://github.com/fsr5-fhaachen/groups/blob/main/LICENSE) licensed.