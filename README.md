![Semantic Query Interface for MediaWiki](https://upload.wikimedia.org/wikipedia/mediawiki/e/e4/Sqi.png)

**Semantic Query Interface** — mediawiki extension developed for devs who use Semantic Mediawiki extension.
It provides class for easy interaction with semantic data.

Current state: **alpha**, subobjects supported.

For getting started guide see: https://www.mediawiki.org/wiki/User:Vedmaka/Semantic_Query_Interface

## Installation
Clone this repository to _extensions_ folder:
```
cd extensions && git clone git@github.com:vedmaka/SemanticQueryInterface.git
```

Add following line to _LocalSettings.php_
```
require_once "$IP/extensions/SemanticQueryInterface/SQI.php";
```
