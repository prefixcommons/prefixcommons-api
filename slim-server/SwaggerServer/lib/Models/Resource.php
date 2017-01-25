<?php
/*
 * Resource
 */
namespace SwaggerServer\lib\Models;

/*
 * Resource
 */
class Resource {
    /* @var string $id Unique identifier representing a specific resource. */
    private $id;
/* @var string $title The title of the resource. */
    private $title;
/* @var string $description Description of resource. */
    private $description;
/* @var string $abbreviation Common abbreviation for the resource. */
    private $abbreviation;
/* @var \SwaggerServer\lib\Models\Organization $responsibleOrganization  */
    private $responsibleOrganization;
/* @var string $license A URL for an online document that describes the licensing information for the resource. */
    private $license;
/* @var string $documentation A URL for an online document that provides a detailed description of the resource. */
    private $documentation;
/* @var \SwaggerServer\lib\Models\Publication[] $publications A list of online publications about the resource. */
    private $publications;
/* @var string $curieExample An example CURIE for the resource where the resource prefix is separated from the local identifier using a colon character &#39;:&#39; */
    private $curieExample;
/* @var string $localIdentifierRegex A regular expression for the local part of a CURIE */
    private $localIdentifierRegex;
/* @var string $localIdentifierExample An examplar local identifier that matches the regex */
    private $localIdentifierExample;
/* @var string $prefixExample An example prefix */
    private $prefixExample;
/* @var \SwaggerServer\lib\Models\Keyword[] $keywords  */
    private $keywords;
/* @var string[] $alternativeIdentifier  */
    private $alternativeIdentifier;
/* @var \SwaggerServer\lib\Models\Prefix[] $prefixes  */
    private $prefixes;
/* @var \SwaggerServer\lib\Models\URIPattern[] $uRIPattern A list of URI patterns for the resource */
    private $uRIPattern;
/* @var \SwaggerServer\lib\Models\Resolver[] $uRLResolver A list of content-type resolvers for a particular resource item */
    private $uRLResolver;
}
