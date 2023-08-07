package test

// An interface representing only the type int.
type x interface {
	int
}

// An interface representing all types with underlying type int.
type x interface {
	~int
}

// An interface representing all types with underlying type int that implement the String method.
type x interface {
	~int
	String() string
}

// An interface representing an empty type set: there is no type that is both an int and a string.
type x interface {
	int
	string
}

// The Float interface represents all floating-point types
// (including any named types whose underlying types are
// either float32 or float64).
type Float interface {
	~float32 | ~float64
}
