# Loaded into the user's Bash/Zsh by `eval "$(bonsai shell-init)"`.
# A subprocess cannot change its parent's cwd. Re-enter the logical current/
# path after its symlink changes, before Git or the prompt inspect the cwd.
export BONSAI_SHELL_ROOT="$_BONSAI_ROOT"
_bonsai_refresh_cwd() {
    local previous_status=$?
    case "$PWD/" in
        "$_BONSAI_ROOT/current/"*)
            if [[ ! . -ef "$PWD" ]]; then
                if [[ -d "$PWD" ]]; then
                    builtin cd -- "$PWD" || return
                elif [[ -d "$_BONSAI_ROOT/current" ]]; then
                    # The new project need not have the previous subdirectory.
                    builtin cd -- "$_BONSAI_ROOT/current" || return
                fi
            fi
            ;;
    esac
    return "$previous_status"
}

bonsai() {
    local bonsai_status
    "$_BONSAI_CLI" "$@"
    bonsai_status=$?
    _bonsai_refresh_cwd
    return "$bonsai_status"
}

if [[ -n ${BASH_VERSION:-} ]]; then
    # Prepend to preserve existing prompt/Git hooks, including array entries.
    if [[ ${PROMPT_COMMAND[0]:-} != _bonsai_refresh_cwd ]]; then
        PROMPT_COMMAND=(_bonsai_refresh_cwd "${PROMPT_COMMAND[@]}")
    fi
elif [[ -n ${ZSH_VERSION:-} ]]; then
    typeset -ga precmd_functions
    if (( ! ${precmd_functions[(Ie)_bonsai_refresh_cwd]} )); then
        precmd_functions=(_bonsai_refresh_cwd "${precmd_functions[@]}")
    fi
fi

# Activation also repairs an existing shell after an earlier unintegrated switch.
_bonsai_refresh_cwd
