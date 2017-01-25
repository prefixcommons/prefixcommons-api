<?php
/*
 * Prefix
 */
namespace SwaggerServer\lib\Models;

/*
 * Prefix
 */
class Prefix {
    /* @var string $label The prefix value */
    private $label;
/* @var object $source The source of the prefix */
    private $source;
/* @var string $sourceRole The role of the source in providing this prefix */
    private $sourceRole;
/* @var \SwaggerServer\lib\Models\Organization[] $usedBy A list of the organizations for which this is a primary prefix */
    private $usedBy;
}
