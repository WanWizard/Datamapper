# Datamapper

[![Build Status](https://secure.travis-ci.org/WanWizard/Datamapper.png?branch=develop)](http://travis-ci.org/WanWizard/Datamapper)

DataMapper is an Object Relational Mapper written in PHP. It is designed to map your Database tables into easy to work with objects, fully aware of the relationships between each other.

## This doesn't look like Datamapper?

Well, it sure looks like Datamapper, a sparkling shining new version of Datamapper that is!

If you are looking for the current Datamapper version for CodeIgniter, please see http://bitbucket.org/wanwizard/datamapper

## So what is this then?

This repository contains the new codebase for Datamapper for PHP.

You may notice it no longer references CodeIgniter in the name, because the new code is designed to support multiple frameworks.

From the start, it will support CodeIgniter (2.0.0+), and FuelPHP (both 1.x and 2.x). In due time separate repositories will be created
for both environments which will enable the Datamapper package in that environment.

For example, CodeIgniter will require installation of the Composer PSR-0 autoloader and a CI library to activate Datamapper within the CodeIgniter environment,
while FuelPHP already contains a PSR-0 compliant autoloader. FuelPHP however requires a package setup, which is different in 1.x and in 2.0.

Datamapper also no longer uses the Frameworks own DBAL (or Query Builder), but instead includes the great multi-platform DBAL library http://github.com/FrenkyNet/Cabinet.
This ensures that Datamapper will perform consistent on all development environments, and will support a full range of RDBMS platforms and even some NoSQL ones!

## When can we use it?

As usual: whenever it's ready. No timeframe has been defined as of now, watch this repository for developments.
