create table plg_AddProduct_tokens(
    token_id text NOT NULL,
    onetime_key text NOT NULL,
    member_id int NOT NULL,
    appli_id text NOT NULL,
    allow_flg smallint DEFAULT 0,
    del_flg smallint DEFAULT 0,
    last_access_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    update_date timestamp NOT NULL,
    PRIMARY KEY (token_id),
    UNIQUE (onetime_key)
);

INSERT INTO mtb_auth_excludes(id,name,rank) VALUES(
    (SELECT MAX(id)+1 FROM mtb_auth_excludes),
    'addproduct/plg_AddProduct_login.php',
    (SELECT MAX(rank)+1 FROM mtb_auth_excludes)
);
