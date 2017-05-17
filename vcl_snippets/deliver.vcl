    if (resp.status >= 500 && resp.status < 600) {
        /* restart if the stale object is available */
        if (stale.exists) {
            restart;
        }
    }

    # Add an easy way to see whether custom Fastly VCL has been uploaded
    if ( req.http.Fastly-Debug ) {
        set resp.http.Fastly-WordPress-VCL-Uploaded = "1.1.1";
    } else {
        remove resp.http.astly-WordPress-VCL-Uploaded;
    }
