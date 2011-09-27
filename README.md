# Introduction

With the linking extension you are able to link a resource in the selected OntoWiki knowledge base to foreign or external resources reported to exist by one of DBpedia, Swoogle or Sindice.
This happens in the context of the OntoWiki detail view on a resource.
A search using keywords is performed and a result list presented.
From this list, choosing the properties of the link, triples can be imported into the active model. 

# Usage

First we need to navigate to a resource.
In the OntoWiki properties page for the resource, on the right hand side a form should show.
You can chose an endpoint (DBpedia, Swoogle or Sindice) a limit(10, 25, 50, 75, 100) and a keyword which should be the rdfs:label or dc:title of the resource if available and no further configuration was performed.
Overriding the keyword is useful if you know what you want to link.
The keyword MUST be given if it is initially empty. In this case 
adding a line to the ini file, so that such a resource may have a default keyword, is advisable.
This is described later in this page and in the ini file itself.
A few seconds after hitting the search button a window with a list of results will pop up.
There are three columns: the first is a label, the second an external resource and the third a drop-down list (combo-box) of properties.
After choosing a value for the drop-down a line corresponds to a triple  `?s ?p ?o` where `?s` is the selected resource in OntoWiki `?p` the value of the drop-down `?o` the search result URI in the second column.
To add another endpoint you need to write a wrapper.
In the class/search subdirectory of the extension is the abstract class SearchEngine which needs to be implemented.
Adapting currently used endpoints requires altering their respective files in aforementioned directory since the specific URLs etc. are hard coded there. 

## Ini File Description

With the new Extension infrastructure the ini file is called `default.ini`.
To configure which properties to use for linking the selected resource with external but somehow related resources, the ini file can be customized.
There are comments repeating or detailing most of the points below in the ini file itself.

### declare prefixes

To have rdf and rdfs shorthands refer to their respective full URI use:

    prefix.rdf  = "http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    prefix.rdfs = "http://www.w3.org/2000/01/rdf-schema#"

It is required to declare in this fashion all URIs used throughout the ini file.

### setup default key word property

    extract_properties.1="rdfs:label"
    extract_properties.2="dc:title"

will use the value of the rdfs:label property from the selected resource
as the default key word. 

### define groups

The content of the combos for linking is defined as sets of properties.
One of the three sets defined below can later be chosen as combo content.

    ;; default
    groups.all[]="owl:sameAs"
    groups.all[]="skos:closeMatch"
    groups.all[]="rdf:type"

    ;; class
    groups.class[]="owl:equivalentClass"
    groups.class[]="rdfs:subClassOf"

    ;; property
    groups.prop[]="owl:equivalentProperty"
    groups.prop[]="rdfs:domain"
    groups.prop[]="rdfs:range"


### fill the combo

Finally, to fill the drop-down list with properties used to link a search result URI to the selected resource in OntoWiki detail view use one of the groups defined above:

    import.rdf:type.owl:Class=class
    import.rdf:type.owl:ObjectProperty=prop
    import.default=all

This states that, should the selected resource have an rdf:type of owl:class, the group named class is used for the contents of the linking properties drop-down list in the extensions result view window. 
The default group in this case all could be made quite large.
But rather it is possible to have multiple ini files and just rename them to quickly switch to another configuration depending on the knowledge base.

