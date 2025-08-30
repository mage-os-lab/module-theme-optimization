    # Not letting browser to cache non-static files and excluded paths by magento bfcache config
    if (req.url ~ "^/(/* {{ excluded_paths }} */)" || resp.http.Cache-Control !~ "private" && req.url !~ "^/(pub/)?(media|static)/") {
        set resp.http.Pragma = "no-cache";
        set resp.http.Expires = "-1";
        set resp.http.Cache-Control = "no-store, no-cache, must-revalidate, max-age=0";
    }
